(() => {
  /** @constant {number} MAX_ATTEMPTS Maximum number of attempts to run H5P Caretaker. */
  const MAX_ATTEMPTS = 50;

  /** @constant {number} DELAY_MS Delay in milliseconds between attempts to run H5P Caretaker. */
  const DELAY_MS = 100;

  // If there's a file to upload, do so after initialization.
  const handleInitialized = () => {
    const url = window.H5PCaretakerIntegration.preloaded.url;
    if (!url) {
      return;
    }
    window.h5pcaretaker.uploadByURL(url);
  };

  // If export file needs to be removed, do so after upload has ended.
  const handleUploadEnded = () => {
    const exportRemoveId = window.H5PCaretakerIntegration.preloaded.id;

    if (!exportRemoveId) {
      return;
    }

    const formData = new FormData();
    if (window.H5PCaretakerIntegration.sessionKeyName) {
      formData.set(window.H5PCaretakerIntegration.sessionKeyName, window.H5PCaretakerIntegration.sessionKeyValue);
    }
    formData.set('id', exportRemoveId);
    formData.set('action', 'remove');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.H5PCaretakerIntegration.ajax.cleanUp, true);
    xhr.send(formData);
  };

  // Initialize H5P Caretaker
  const runH5PCaretaker = () => {
    window.h5pcaretaker = new window.H5PCaretaker(
      {
        endpoint: window.H5PCaretakerIntegration.ajax.upload,
        sessionKeyName: window.H5PCaretakerIntegration.sessionKeyName,
        sessionKeyValue: window.H5PCaretakerIntegration.sessionKeyValue,
        l10n: {
          orDragTheFileHere: window.H5PCaretakerIntegration.l10n.orDragTheFileHere,
          removeFile: window.H5PCaretakerIntegration.l10n.removeFile,
          selectYourLanguage: window.H5PCaretakerIntegration.l10n.selectYourLanguage,
          uploadProgress: window.H5PCaretakerIntegration.l10n.uploadProgress,
          uploadYourH5Pfile: window.H5PCaretakerIntegration.l10n.uploadYourH5Pfile,
          yourFileIsBeingChecked: window.H5PCaretakerIntegration.l10n.yourFileIsBeingChecked,
          yourFileWasCheckedSuccessfully: window.H5PCaretakerIntegration.l10n.yourFileWasCheckedSuccessfully,
          totalMessages: window.H5PCaretakerIntegration.l10n.totalMessages,
          issues: window.H5PCaretakerIntegration.l10n.issues,
          results: window.H5PCaretakerIntegration.l10n.results,
          filterBy: window.H5PCaretakerIntegration.l10n.filterBy,
          groupBy: window.H5PCaretakerIntegration.l10n.groupBy,
          download: window.H5PCaretakerIntegration.l10n.download,
          showDetails: window.H5PCaretakerIntegration.l10n.showDetails,
          hideDetails: window.H5PCaretakerIntegration.l10n.hideDetails,
          allFilteredOut: window.H5PCaretakerIntegration.l10n.allFilteredOut,
          contentFilter: window.H5PCaretakerIntegration.l10n.contentFilter,
          showAll: window.H5PCaretakerIntegration.l10n.showAll,
          showSelected: window.H5PCaretakerIntegration.l10n.showSelected,
          showNone: window.H5PCaretakerIntegration.l10n.showNone,
          filterByContent: window.H5PCaretakerIntegration.l10n.filterByContent,
          reset: window.H5PCaretakerIntegration.l10n.reset,
          unknownError: window.H5PCaretakerIntegration.l10n.unknownError,
          checkServerLog: window.H5PCaretakerIntegration.l10n.checkServerLog,
        },
      },
      {
        onInitialized: () => {
          handleInitialized();
        },
        onUploadEnded: () => {
          handleUploadEnded();
        }
      }
    );
  }

  // Wait for H5PCaretaker to be available before running it.
  const waitForH5PCaretaker = (delay = DELAY_MS, maxattempts = MAX_ATTEMPTS, attempts = MAX_ATTEMPTS) => {
    if (window.H5PCaretaker) {
      runH5PCaretaker();
    } else if (attempts <= 0) {
      console.warn(`H5PCaretaker not found after ${maxattempts} attempts.`);
    } else {
      setTimeout(() => {
        waitForH5PCaretaker(delay, maxattempts, attempts - 1);
      }, delay);
    }
  };

  if (document.readyState !== 'loading') {
    waitForH5PCaretaker();
  }
  else {
    document.addEventListener('DOMContentLoaded', () => {
      waitForH5PCaretaker();
    });
  }
})()
