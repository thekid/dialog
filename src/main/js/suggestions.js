function suggestions($search, fulltext) {
  const $input = $search.querySelector('input[name="q"]');
  const $suggestions = $search.querySelector('.suggestions');
  const html = function(input) {
    return input.replace(/[<>&]/g, c => '&#' + c.charCodeAt(0) + ';');
  };
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

        let list = '';
        for (const suggestion of suggestions) {
          list += `<li role="option" aria-selected="false">
            <a href="${suggestion.link}">
              <span class="title"><span class="query">${html(suggestion.title).replace(pattern, '<em>$1</em>')}</span></span>
              <span class="locations">${html(suggestion.at.join(' / '))}</span>
              <span class="date">${suggestion.date}</span>
            </a>
          </li>`;
        }

        // Show fulltext option at end of search
        if (fulltext) {
          list += `<li role="option" aria-selected="false">
            <a class="fulltext" href="/search?q=${encoded}">
              <span class="title">${fulltext.replace('%s', '<span class="query"><em>' + html(query) + '</em></span>')}</span>
            </a>
          </li>`;
        }

        if ($suggestions.innerHTML = list) {
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
    debounce = setTimeout(search, 150);
  });
  $input.addEventListener('blur', e => {
    setTimeout(() => $search.classList.remove('suggesting'), 150);
  });
  $input.addEventListener('focus', e => {
    if ($suggestions.innerHTML) {
      $search.classList.add('suggesting');
    }
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