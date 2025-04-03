<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This hook adds an edit button just right the preview button in element infomation view.
 *
 * This class is a copy of the ElementInformationController.
 *
 * In case of upgrade adopt following modifications:
 * - Add isValid() method with return value true
 * - Add render($type, ElementInformationController $elementInformationController) method which calls init and main
 * - $request -> $GLOBALS['TYPO3_REQUEST']
 * - Set return value of main to string and return that string in render method
 * - Add a section to build the edit button in getPreview()
 */
class ModifyElementInformationHook
{
    protected string $table;

    protected string $uid;

    protected string $permsClause;

    protected bool $access = false;

    /**
     * Which type of element:
     * - "file"
     * - "db"
     */
    protected string $type = '';

    /**
     * For type "db": Set to page record of the parent page of the item set
     * (if type="db")
     */
    protected array $pageInfo;

    /**
     * Database records identified by table/uid
     */
    protected array $row;

    protected ?File $fileObject = null;

    protected ?Folder $folderObject = null;

    protected IconFactory $iconFactory;

    protected UriBuilder $uriBuilder;

    protected ViewFactoryInterface $viewFactory;

    public function __construct(ViewFactoryInterface $viewFactory)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
    }

    public function isValid(string $type, ElementInformationController $elementInformationController): bool
    {
        return true;
    }

    public function render(string $type, ElementInformationController $elementInformationController): string
    {
        $this->init($GLOBALS['TYPO3_REQUEST']);

        return $this->main($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Determines if table/uid point to database record or file and
     * if user has access to view information
     */
    protected function init(ServerRequestInterface $request): void
    {
        $queryParams = $request->getQueryParams();
        $this->table = $queryParams['table'] ?? null;
        $this->uid = $queryParams['uid'] ?? null;

        $this->permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        if (isset($GLOBALS['TCA'][$this->table])) {
            $this->initDatabaseRecord();
        } elseif ($this->table === '_FILE' || $this->table === '_FOLDER' || $this->table === 'sys_file') {
            $this->initFileOrFolderRecord();
        }
    }

    /**
     * Init database records (table)
     */
    protected function initDatabaseRecord(): void
    {
        $this->type = 'db';
        $this->uid = (int)$this->uid;

        // Check permissions and uid value:
        if ($this->uid && $this->getBackendUser()->check('tables_select', $this->table)) {
            if ((string)$this->table === 'pages') {
                $this->pageInfo = BackendUtility::readPageAccess($this->uid, $this->permsClause) ?: [];
                $this->access = $this->pageInfo !== [];
                $this->row = $this->pageInfo;
            } else {
                $this->row = BackendUtility::getRecordWSOL($this->table, $this->uid);
                if ($this->row) {
                    if ((int)$this->row['t3ver_oid'] !== 0) {
                        // Make $this->uid the uid of the versioned record, while $this->row['uid'] is live record uid
                        $this->uid = (int)$this->row['_ORIG_uid'];
                    }

                    $this->pageInfo = BackendUtility::readPageAccess((int)$this->row['pid'], $this->permsClause) ?: [];
                    $this->access = $this->pageInfo !== [];
                }
            }
        }
    }

    /**
     * Init file/folder parameters
     */
    protected function initFileOrFolderRecord(): void
    {
        $fileOrFolderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->uid);
        if ($fileOrFolderObject instanceof Folder) {
            $this->folderObject = $fileOrFolderObject;
            $this->access = $this->folderObject->checkActionPermission('read');
            $this->type = 'folder';
        } elseif ($fileOrFolderObject instanceof File) {
            $this->fileObject = $fileOrFolderObject;
            $this->folderObject = null;
            $this->access = $this->fileObject->checkActionPermission('read');
            $this->type = 'file';
            $this->table = 'sys_file';

            try {
                $this->row = BackendUtility::getRecordWSOL($this->table, $fileOrFolderObject->getUid());
            } catch (\Exception $e) {
                $this->row = [];
            }
        }
    }

    protected function main(ServerRequestInterface $request): string
    {
        $viewFactoryData = new ViewFactoryData(
            partialRootPaths: GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials'),
            layoutRootPaths: GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts'),
            templatePathAndFilename: GeneralUtility::getFileAbsFileName('EXT:jwtools2/Resources/Private/Templates/ContentElement/ElementInformation.html'),
            request: $request,
        );
        $view = $this->viewFactory->create($viewFactoryData);
        // Rendering of the output via fluid
        $view->assign('accessAllowed', true);
        $view->assignMultiple($this->getPageTitle());
        $view->assignMultiple($this->getPreview($request));
        $view->assignMultiple($this->getPropertiesForTable());
        $view->assignMultiple($this->getReferences($request));
        $view->assign(
            'returnUrl',
            GeneralUtility::sanitizeLocalUrl(
                $request->getQueryParams()['returnUrl'] ?? GeneralUtility::linkThisScript(),
            ),
        );
        $view->assign('maxTitleLength', $this->getBackendUser()->uc['titleLen'] ?? 20);

        return $view->render();
    }

    /**
     * Get page title with icon, table title and record title
     */
    protected function getPageTitle(): array
    {
        $pageTitle = [
            'title' => BackendUtility::getRecordTitle($this->table, $this->row),
        ];
        if ($this->type === 'folder') {
            $pageTitle['title'] = htmlspecialchars($this->folderObject->getName());
            $pageTitle['table'] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:folder');
            $pageTitle['icon'] = $this->iconFactory->getIconForResource($this->folderObject, IconSize::SMALL)->render();
        } elseif ($this->type === 'file') {
            $pageTitle['table'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
            $pageTitle['icon'] = $this->iconFactory->getIconForResource($this->fileObject, IconSize::SMALL)->render();
        } else {
            $pageTitle['table'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
            $pageTitle['icon'] = $this->iconFactory->getIconForRecord($this->table, $this->row, IconSize::SMALL);
        }

        return $pageTitle;
    }

    /**
     * Get preview for current record
     */
    protected function getPreview(ServerRequestInterface $request): array
    {
        $preview = [];
        // Perhaps @todo in future: Also display preview for records - without fileObject
        if (!$this->fileObject instanceof File) {
            return $preview;
        }

        // check if file is marked as missing
        if ($this->fileObject->isMissing()) {
            $preview['missingFile'] = $this->fileObject->getName();
        } else {
            $rendererRegistry = GeneralUtility::makeInstance(RendererRegistry::class);
            $fileRenderer = $rendererRegistry->getRenderer($this->fileObject);
            $preview['url'] = $this->fileObject->getPublicUrl(true) ?? '';
            $preview['editUrl'] = '';

            if (
                ($fileMetaUid = $this->fileObject->getProperties()['metadata_uid'] ?? false)
                && $this->getBackendUser()->check('tables_modify', 'sys_file')
                && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata')
            ) {
                $urlParameters = [
                    'edit' => [
                        'sys_file_metadata' => [
                            $fileMetaUid => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUrl(),
                ];
                $preview['editUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            }

            $width = min(590, $this->fileObject->getMetaData()['width'] ?? 590) . 'm';
            $height = min(400, $this->fileObject->getMetaData()['height'] ?? 400) . 'm';

            // Check if there is a FileRenderer
            if ($fileRenderer !== null) {
                $preview['fileRenderer'] = $fileRenderer->render($this->fileObject, $width, $height);
            } elseif ($this->fileObject->isImage()) {
                // else check if we can create an Image preview
                $preview['fileObject'] = $this->fileObject;
                $preview['width'] = $width;
                $preview['height'] = $height;
            }
        }

        return $preview;
    }

    /**
     * Get property array for html table
     */
    protected function getPropertiesForTable(): array
    {
        $lang = $this->getLanguageService();
        $propertiesForTable = [];
        $propertiesForTable['extraFields'] = $this->getExtraFields();

        // Traverse the list of fields to display for the record:
        $fieldList = $this->getFieldList($this->table, (int)($this->row['uid'] ?? 0));

        foreach ($fieldList as $name) {
            $name = trim($name);
            $uid = $this->row['uid'] ?? 0;

            if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
                continue;
            }

            // @todo Add meaningful information for mfa field. For the time being we don't display anything at all.
            if ($this->type === 'db' && $name === 'mfa' && in_array($this->table, ['be_users', 'fe_users'], true)) {
                continue;
            }

            // not a real field -> skip
            if ($this->type === 'file' && $name === 'fileinfo') {
                continue;
            }

            // Field does not exist (e.g. having type=none) -> skip
            if (!array_key_exists($name, $this->row)) {
                continue;
            }

            $isExcluded = ($GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] ?? false) && !$this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $name);
            if ($isExcluded) {
                continue;
            }

            $label = $lang->sL(BackendUtility::getItemLabel($this->table, $name));
            $label = $label ?: $name;

            $propertiesForTable['fields'][] = [
                'fieldValue' => BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, false, false, $uid),
                'fieldLabel' => htmlspecialchars($label),
            ];
        }

        // additional information for folders and files
        if ($this->folderObject instanceof Folder || $this->fileObject instanceof File) {
            // storage
            if ($this->folderObject instanceof Folder) {
                $propertiesForTable['fields']['storage'] = [
                    'fieldValue' => $this->folderObject->getStorage()->getName(),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.storage')),
                ];
            }

            // folder
            $resourceObject = $this->fileObject ?: $this->folderObject;
            $propertiesForTable['fields']['folder'] = [
                'fieldValue' => $resourceObject->getParentFolder()->getReadablePath(),
                'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:folder')),
            ];

            if ($this->fileObject instanceof File) {
                // show file dimensions for images
                if ($this->fileObject->getType() === FileType::IMAGE->value) {
                    $propertiesForTable['fields']['width'] = [
                        'fieldValue' => $this->fileObject->getProperty('width') . 'px',
                        'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.width')),
                    ];
                    $propertiesForTable['fields']['height'] = [
                        'fieldValue' => $this->fileObject->getProperty('height') . 'px',
                        'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.height')),
                    ];
                }

                // file size
                $propertiesForTable['fields']['size'] = [
                    'fieldValue' => GeneralUtility::formatSize((int)$this->fileObject->getProperty('size'), htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits'))),
                    'fieldLabel' => $lang->sL(BackendUtility::getItemLabel($this->table, 'size')),
                ];

                // show the metadata of a file as well
                $table = 'sys_file_metadata';
                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                /** @var array<string, string> $metaData */
                $metaData = $metaDataRepository->findByFileUid($this->row['uid']);
                $allowedFields = $this->getFieldList($table, (int)$metaData['uid']);

                foreach ($metaData as $name => $value) {
                    if (in_array($name, $allowedFields, true)) {
                        if (!isset($GLOBALS['TCA'][$table]['columns'][$name])) {
                            continue;
                        }

                        $isExcluded = ($GLOBALS['TCA'][$table]['columns'][$name]['exclude'] ?? false) && !$this->getBackendUser()->check('non_exclude_fields', $table . ':' . $name);
                        if ($isExcluded) {
                            continue;
                        }

                        $label = $lang->sL(BackendUtility::getItemLabel($table, $name));
                        $label = $label ?: $name;

                        $propertiesForTable['fields'][] = [
                            'fieldValue' => BackendUtility::getProcessedValue($table, $name, $metaData[$name], 0, false, false, (int)$metaData['uid']),
                            'fieldLabel' => htmlspecialchars($label),
                        ];
                    }
                }
            }
        }

        return $propertiesForTable;
    }

    /**
     * Get the list of fields that should be shown for the given table
     */
    protected function getFieldList(string $table, int $uid): array
    {
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $table,
            'vanillaUid' => $uid,
        ];
        try {
            $result = $formDataCompiler->compile($formDataCompilerInput);
            $fieldList = array_unique(array_values($result['columnsToProcess']));

            $ctrlKeysOfUnneededFields = ['origUid', 'transOrigPointerField', 'transOrigDiffSourceField'];
            foreach ($ctrlKeysOfUnneededFields as $field) {
                if (isset($GLOBALS['TCA'][$table]['ctrl'][$field]) && ($key = array_search($GLOBALS['TCA'][$table]['ctrl'][$field], $fieldList, true)) !== false) {
                    unset($fieldList[$key]);
                }
            }
        } catch (\Exception $exception) {
            $fieldList = [];
        }

        $searchFields = GeneralUtility::trimExplode(',', ($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''));

        return array_unique(array_merge($fieldList, $searchFields));
    }

    /**
     * Get the extra fields (uid, timestamps, creator) for the table
     */
    protected function getExtraFields(): array
    {
        $lang = $this->getLanguageService();
        $keyLabelPair = [];
        if (in_array($this->type, ['folder', 'file'], true)) {
            if ($this->type === 'file') {
                $keyLabelPair['uid'] = [
                    'value' => (int)$this->row['uid'],
                ];
                $keyLabelPair['creation_date'] = [
                    'value' => BackendUtility::datetime($this->row['creation_date']),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate')),
                    'isDatetime' => true,
                ];
                $keyLabelPair['modification_date'] = [
                    'value' => BackendUtility::datetime($this->row['modification_date']),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.timestamp')),
                    'isDatetime' => true,
                ];
            }
        } else {
            $keyLabelPair['uid'] = [
                'value' => BackendUtility::getProcessedValueExtra($this->table, 'uid', $this->row['uid']),
                'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:show_item.php.uid')), ':'),
            ];
            foreach (['crdate' => 'creationDate', 'tstamp' => 'timestamp', 'cruser_id' => 'creationUserId'] as $field => $label) {
                if (isset($GLOBALS['TCA'][$this->table]['ctrl'][$field])) {
                    if ($field === 'crdate' || $field === 'tstamp') {
                        $keyLabelPair[$field] = [
                            'value' => BackendUtility::datetime($this->row[$GLOBALS['TCA'][$this->table]['ctrl'][$field]]),
                            'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.' . $label)), ':'),
                            'isDatetime' => true,
                        ];
                    }

                    if ($field === 'cruser_id') {
                        $rowValue = BackendUtility::getProcessedValueExtra($this->table, $GLOBALS['TCA'][$this->table]['ctrl'][$field], $this->row[$GLOBALS['TCA'][$this->table]['ctrl'][$field]]);
                        if ($rowValue) {
                            $creatorRecord = BackendUtility::getRecord('be_users', (int)$rowValue);
                            if ($creatorRecord) {
                                /** @var Avatar $avatar */
                                $avatar = GeneralUtility::makeInstance(Avatar::class);
                                $creatorRecord['icon'] = $avatar->render($creatorRecord);
                                $rowValue = $creatorRecord;
                                $keyLabelPair['creatorRecord'] = [
                                    'value' => $rowValue,
                                    'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.' . $label)), ':'),
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $keyLabelPair;
    }

    /**
     * Get references section (references from and references to current record)
     */
    protected function getReferences(ServerRequestInterface $request): array
    {
        $references = [];
        switch ($this->type) {
            case 'db': {
                $references['refLines'] = $this->makeRef($this->table, $this->uid, $request);
                $references['refFromLines'] = $this->makeRefFrom($this->table, $this->uid, $request);
                break;
            }

            case 'file': {
                if ($this->fileObject && $this->fileObject->isIndexed()) {
                    $references['refLines'] = $this->makeRef('_FILE', $this->fileObject, $request);
                }

                break;
            }
        }

        return $references;
    }

    /**
     * Get field name for specified table/column name
     */
    protected function getLabelForColumnByTable(string $tableName, string $fieldName): string
    {
        if (($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label'] ?? null) !== null) {
            $field = $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
            if (trim($field) === '') {
                $field = $fieldName;
            }
        } else {
            $field = $fieldName;
        }

        return $field;
    }

    /**
     * Returns the record actions
     * @throws RouteNotFoundException
     */
    protected function getRecordActions(string $table, int $uid, ServerRequestInterface $request): array
    {
        if ($table === '' || $uid < 0) {
            return [];
        }

        $actions = [];
        // Edit button
        $urlParameters = [
            'edit' => [
                $table => [
                    $uid => 'edit',
                ],
            ],
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUrl(),
        ];
        $actions['recordEditUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        // History button
        $urlParameters = [
            'element' => $table . ':' . $uid,
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUrl(),
        ];
        $actions['recordHistoryUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_history', $urlParameters);

        if ($table === 'pages') {
            // Recordlist button
            $actions['webListUrl'] = (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $uid, 'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUrl()]);

            // View page button
            $actions['viewOnClick'] = PreviewUriBuilder::create($uid)->buildDispatcherDataAttributes();
        }

        return $actions;
    }

    /**
     * Make reference display
     *
     * @throws RouteNotFoundException|Exception
     */
    protected function makeRef(string $table, File|int|string $ref, ServerRequestInterface $request): array
    {
        $refLines = [];
        $lang = $this->getLanguageService();
        // Files reside in sys_file table
        if ($table === '_FILE') {
            $selectTable = 'sys_file';
            $selectUid = $ref->getUid();
        } else {
            $selectTable = $table;
            $selectUid = $ref;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $predicates = [
            $queryBuilder->expr()->eq(
                'ref_table',
                $queryBuilder->createNamedParameter($selectTable, Connection::PARAM_STR),
            ),
            $queryBuilder->expr()->eq(
                'ref_uid',
                $queryBuilder->createNamedParameter($selectUid, Connection::PARAM_INT),
            ),
        ];

        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            $allowedSelectTables = GeneralUtility::trimExplode(',', $backendUser->groupData['tables_select']);
            $predicates[] = $queryBuilder->expr()->in(
                'tablename',
                $queryBuilder->createNamedParameter($allowedSelectTables, Connection::PARAM_STR_ARRAY),
            );
        }

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$predicates)
            ->executeQuery()
            ->fetchAllAssociative();

        // Compile information for title tag:
        foreach ($rows as $row) {
            if ($row['tablename'] === 'sys_file_reference') {
                $row = $this->transformFileReferenceToRecordReference($row);
                if ($row['tablename'] === null || $row['recuid'] === null) {
                    return [];
                }
            }

            $line = [];
            $record = BackendUtility::getRecordWSOL($row['tablename'], $row['recuid']);
            if ($record) {
                if (!$this->canAccessPage($row['tablename'], $record)) {
                    continue;
                }

                $parentRecord = BackendUtility::getRecord('pages', $record['pid']);
                $parentRecordTitle = is_array($parentRecord)
                    ? BackendUtility::getRecordTitle('pages', $parentRecord)
                    : '';
                $urlParameters = [
                    'edit' => [
                        $row['tablename'] => [
                            $row['recuid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUrl(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, IconSize::SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['tablename'], $record, false, true);
                $line['parentRecord'] = $parentRecord;
                $line['parentRecordTitle'] = $parentRecordTitle;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title']);
                $line['labelForTableColumn'] = $this->getLabelForColumnByTable($row['tablename'], $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['tablename'], $row['recuid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'] ?? '') ?: $row['tablename'];
                $line['labelForTableColumn'] = $this->getLabelForColumnByTable($row['tablename'], $row['field']);
            }

            $refLines[] = $line;
        }

        return $refLines;
    }

    /**
     * Make reference display (what this elements points to)
     */
    protected function makeRefFrom(string $table, string $ref, ServerRequestInterface $request): array
    {
        $refFromLines = [];
        $lang = $this->getLanguageService();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $predicates = [
            $queryBuilder->expr()->eq(
                'tablename',
                $queryBuilder->createNamedParameter($table, Connection::PARAM_STR),
            ),
            $queryBuilder->expr()->eq(
                'recuid',
                $queryBuilder->createNamedParameter($ref, Connection::PARAM_INT),
            ),
        ];

        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            $allowedSelectTables = GeneralUtility::trimExplode(',', $backendUser->groupData['tables_select']);
            $predicates[] = $queryBuilder->expr()->in(
                'ref_table',
                $queryBuilder->createNamedParameter($allowedSelectTables, Connection::PARAM_STR_ARRAY),
            );
        }

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$predicates)
            ->executeQuery()
            ->fetchAllAssociative();

        // Compile information for title tag:
        foreach ($rows as $row) {
            $line = [];
            $record = BackendUtility::getRecordWSOL($row['ref_table'], $row['ref_uid']);
            if ($record) {
                if (!$this->canAccessPage($row['ref_table'], $record)) {
                    continue;
                }

                $urlParameters = [
                    'edit' => [
                        $row['ref_table'] => [
                            $row['ref_uid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, IconSize::SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['ref_table'], $record, false, true);
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'] ?? '');
                $line['labelForTableColumn'] = $this->getLabelForColumnByTable($table, $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['ref_table'], $row['ref_uid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'] ?? '');
                $line['labelForTableColumn'] = $this->getLabelForColumnByTable($table, $row['field']);
            }

            $refFromLines[] = $line;
        }

        return $refFromLines;
    }

    /**
     * Convert FAL file reference (sys_file_reference) to reference index (sys_refindex) table format
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $fileReference = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        return [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign'],
        ];
    }

    protected function canAccessPage(string $tableName, array $record): bool
    {
        $recordPid = (int)($tableName === 'pages' ? $record['uid'] : $record['pid']);
        return $this->getBackendUser()->isInWebMount($tableName === 'pages' ? $record : $record['pid'])
            || ($recordPid === 0 && !empty($GLOBALS['TCA'][$tableName]['ctrl']['security']['ignoreRootLevelRestriction']));
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
