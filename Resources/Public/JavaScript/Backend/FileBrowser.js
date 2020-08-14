define([], () => {
  const prependFileBrowserToElement = element => {
    element.prepend(
      document
        .querySelector('[action^="/typo3/index.php?route=%2Ffile%2Fcommit"]')
        .cloneNode(true)
    );
  };

  const observer = new MutationObserver((mutations, observer) => {
    const fileBrowser = document.querySelector(
      "div.element-browser-main-content div.element-browser-body"
    );
    if (fileBrowser) {
      prependFileBrowserToElement(fileBrowser);
      observer.disconnect();
    }
  });

  observer.observe(document, {
    childList: true,
    subtree: true,
  });
});
