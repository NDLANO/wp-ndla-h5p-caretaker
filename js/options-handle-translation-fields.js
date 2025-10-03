(() => {
  /**
   * Initialize the script once the DOM is fully loaded.
   */
  const initialize = () => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        initializeAllTranslationFields();
      });
    }
    else {
      initializeAllTranslationFields();
    }
  }

  /**
   * Initialize all translation fields on the page.
   */
  const initializeAllTranslationFields = () => {
    document.querySelectorAll('.translation-field').forEach((translationField) => {
      initializeTranslationField(translationField);
    });
  };

  /**
   * Initialize event listeners for a translation field.
   * @param {HTMLElement} translationField The translation field element.
   */
  const initializeTranslationField = (translationField) => {
    if (!(translationField instanceof HTMLElement)) {
      return;
    }

    toggleTranslationVisibility(translationField);

    translationField.addEventListener('click', (event) => {
      handleClick(event);
    });
  };

  /**
   * Toggle the visibility of a translation field.
   * @param {HTMLElement} reference Reference element to find the translation field from.
   * @param {boolean} [forceState] Explicit target state or inferred from content.
   * @returns
   */
  const toggleTranslationVisibility = (reference, forceState) => {
    const translationField = findTranslationField(reference);
    if (!(translationField instanceof HTMLElement)) {
      return;
    }

    let shouldHide = forceState;
    if (typeof shouldHide !== 'boolean') {
      const text = translationField.querySelector('.wp-editor-wrap .wp-editor-area')?.textContent;
      if (typeof text !== 'string') {
        return;
      }
      shouldHide = text.trim() === '';
    }

    translationField.classList.toggle('hidden-translation', shouldHide);

    const button = translationField.querySelector('.toggle-visibility');
    updateButtonText(button, shouldHide);
  };

  /**
   * Find the closest translation field element.
   * @param {HTMLElement} reference Reference element to find the translation field from.
   * @returns {HTMLElement|null} The found translation field or null.
   */
  const findTranslationField = (reference) => {
    if (!(reference instanceof HTMLElement)) {
      return null;
    }

    if (reference.classList.contains('translation-field')) {
      return reference;
    }

    return reference.closest('.translation-field') || reference.querySelector('.translation-field');
  };

  /**
   * Update the button text based on visibility state.
   * @param {HTMLElement} button The button element to update.
   * @param {boolean} toBeHidden Whether the translation field is hidden.
   */
  const updateButtonText = (button, toBeHidden) => {
    if (!(button instanceof HTMLElement) || !window.ndlaTranslationFields) {
      return;
    }

    button.textContent = toBeHidden ? window.ndlaTranslationFields.Show : window.ndlaTranslationFields.Hide;
  };

  /**
   * Handle click on the translation field header.
   * @param {MouseEvent} event Mouse event.
   */
  const handleClick = (event) => {
    const wasHeaderClicked = event.target.classList.contains('translation-field-header') ||
      event.target.closest('.translation-field-header');

    if (!wasHeaderClicked) {
      return;
    }

    const translationField = event.currentTarget;
    const isHidden = translationField.classList.contains('hidden-translation');
    toggleTranslationVisibility(translationField, !isHidden);
  };

  // Initialize field handling
  initialize();
})();
