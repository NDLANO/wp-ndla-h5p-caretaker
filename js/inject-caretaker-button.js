(() => {
  const injectButton = () => {
    const lastButton = document.querySelector('.wrap > h2 > a:last-of-type');
    if (!lastButton) {
      return;
    }

    const caretakerButton = document.createElement('a');
    caretakerButton.href = window.H5PCaretakerButton?.url ?? '';
    // The margin is not consistent for some reason, temporary workaround.
    caretakerButton.style.marginLeft = '10px';
    caretakerButton.target = '_blank';
    caretakerButton.classList.add('add-new-h2');
    caretakerButton.textContent = window.H5PCaretakerButton?.label ?? 'H5P Caretaker';
    lastButton.parentNode.insertBefore(caretakerButton, lastButton.nextSibling);
  };

  if (document.readyState !== 'loading') {
    injectButton();
  }
  else {
    document.addEventListener('DOMContentLoaded', () => {
      injectButton();
    });
  }
})();
