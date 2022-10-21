function suggestions($search, fulltext) {
  const $input = $search.querySelector('input[name="q"]');
  const $suggestions = $search.querySelector('.suggestions');
  const search = function() {
    const query = $input.value.trim();
    if (query.length < 2) {
      $search.classList.remove('suggesting');
      return;
    }

    const encoded = encodeURIComponent(query);
    fetch('/api/suggestions?q=' + encoded)
      .then(res => res.json())
      .then(suggestions => {
        const pattern = new RegExp(`(${query})`, 'i');

        let html = '';
        for (const suggestion of suggestions) {
          html += `<li role="option" aria-selected="false">
            <a href="${suggestion.link}"><span class="query">${suggestion.title.replace(pattern, '<em>$1</em>')}</span></a>
            <span class="date">${suggestion.date}</span>
          </li>`;
        }

        // Show fulltext option at end of search
        if (fulltext) {
          const term = query.replace(/[<>&]/g, c => '&#' + c.charCodeAt(0) + ';');
          html += `<li role="option" aria-selected="false">
            <a class="fulltext" href="/search?q=${encoded}">${fulltext.replace('%s', '<span class="query"><em>' + term + '</em></span>')}</a>
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
  };
  const select = function($target) {
    if (null === $target) return;

    $target.ariaSelected = true;
    $input.value = $target.querySelector('a .query').innerText;
  };

  // Set up input listener
  let debounce = null;
  $input.addEventListener('input', e => {
    if (debounce) clearTimeout(debounce);
    debounce = setTimeout(search, 200);
  });
  $input.addEventListener('blur', e => {
    $search.classList.remove('suggesting');
  });

  // Select suggestions by key
  $input.addEventListener('keydown', e => {
    let $selected;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        $selected = $suggestions.querySelector('li[aria-selected="true"]');
        if ($selected) {
          $selected.ariaSelected = false;
          select($selected.nextSibling ?? $suggestions.querySelector('li:first-child'));
        } else {
          select($suggestions.querySelector('li:first-child'));
        }
        break;

      case 'ArrowUp':
        e.preventDefault();
        $selected = $suggestions.querySelector('li[aria-selected="true"]');
        if ($selected) {
          $selected.ariaSelected = false;
          select($selected.previousSibling ?? $suggestions.querySelector('li:last-child'));
        } else {
          select($suggestions.querySelector('li:last-child'));
        }
        break;

      case 'Escape':
        $search.classList.remove('suggesting');
        break;

      case 'Enter':
        $selected = $suggestions.querySelector('li[aria-selected="true"]');
        if ($selected) {
          e.preventDefault();
          document.location.href = $selected.querySelector('a').href;
        }
        break;
    }
  });
}