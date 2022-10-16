function suggestions($search) {
  const $input = $search.querySelector('input[name="q"]');
  const $suggestions = $search.querySelector('.suggestions');
  const search = function() {
    const query = $input.value.trim();
    if (query.length < 2) {
      $search.classList.remove('suggesting');
      return;
    }

    fetch('/api/suggestions?q=' + encodeURIComponent(query))
      .then(res => res.json())
      .then(suggestions => {
        const pattern = new RegExp(`(${query})`, 'i');

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
  };
  const select = function($target) {
    if (null === $target) return;

    $target.classList.add('selected');
    $input.value = $target.querySelector('a').innerText;
  };

  // Set up input listener
  let debounce = null;
  $input.addEventListener('input', e => {
    if (debounce) clearTimeout(debounce);
    debounce = setTimeout(search, 200);
  });

  // Select suggestions by key
  $input.addEventListener('keydown', e => {
    let $selected;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        $selected = $suggestions.querySelector('li.selected');
        if ($selected) {
          $selected.classList.remove('selected');
          select($selected.nextSibling);
        } else {
          select($suggestions.querySelector('li:first-child'));
        }
        break;

      case 'ArrowUp':
        e.preventDefault();
        $selected = $suggestions.querySelector('li.selected');
        if ($selected) {
          $selected.classList.remove('selected');
          select($selected.previousSibling);
        } else {
          select($suggestions.querySelector('li:last-child'));
        }
        break;

      case 'Escape':
        $search.classList.remove('suggesting');
        break;

      case 'Enter':
        $selected = $suggestions.querySelector('li.selected');
        if ($selected) {
          e.preventDefault();
          document.location.href = $selected.querySelector('a').href;
        }
        break;
    }
  });
}