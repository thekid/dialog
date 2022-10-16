function suggestions($search) {
  const $input = $search.querySelector('input[name="q"]');
  const $suggestions = $search.querySelector('.suggestions');
  const search = function() {
    if ($input.value.length < 2) {
      $search.classList.remove('suggesting');
      return;
    }

    fetch('/api/suggestions?q=' + encodeURIComponent($input.value))
      .then(res => res.json())
      .then(suggestions => {
        const pattern = new RegExp(`(${$input.value})`, 'i');

        let html = '';
        for (const suggestion of suggestions) {
          html += `<li>
            <a href="${suggestion.link}">${suggestion.title.replace(pattern, '<em>$1</em>')}</a>
            <span class="date">${suggestion.date}</span>
          </li>`;
        }
        $suggestions.innerHTML = html;

        if (html) {
          $search.classList.add('suggesting');
        } else {
          $search.classList.remove('suggesting');
        }
      })
    ;
  }

  let debounce = null;
  $input.addEventListener('input', e => {
    if (debounce) clearTimeout(debounce);
    debounce = setTimeout(search, 200);
  });
}