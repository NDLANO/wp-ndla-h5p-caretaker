(() => {
  const overrideToolMenuEntry = () => {
    const menuItem = document.querySelector(`a[href*='${window.H5PCaretakerToolMenuItem?.url}']:not(.add-new-h2)`);
    if (menuItem) {
      menuItem.setAttribute('target', '_blank');
    }
  };

  if (document.readyState !== 'loading') {
    overrideToolMenuEntry();
  }
  else {
    document.addEventListener('DOMContentLoaded', () => {
      overrideToolMenuEntry();
    });
  }
})();
