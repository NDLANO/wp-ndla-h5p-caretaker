// Update the output URL when the input changes.
(() => {
  const input = document.getElementById('url');
  if (!input) {
    return;
  }

  const output = document.getElementById('output-url');
  if (!output) {
    return;
  }

  const prefix = H5PCaretakerOptions.prefix;
  let previous = input.value;

  input.addEventListener('change', () => {
    const inputOrPlaceholder = input.value || H5PCaretakerOptions.placeholder;

    output.textContent = output.textContent.replace(
      `${prefix}${previous}`,
      `${prefix}${inputOrPlaceholder}`
    );

    previous = inputOrPlaceholder;
  });
})();
