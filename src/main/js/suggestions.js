class Suggestions {
  constructor(api, delay = 150) {
    this.api = api;
    this.delay = delay;
  }

  /** Supply link and text for default item */
  default(link, text) {
    this.default = {link, text};
    return this;
  }

  /** Escape input for use in HTML */
  html(input) {
    return input.replace(/[<>&]/g, c => '&#' + c.charCodeAt(0) + ';');
  }

  /** Perform search and return then-able response */
  search(query) {
    return fetch(this.api.replace('%s', encodeURIComponent(query))).then(res => res.json());
  }

  /** Attach suggestion to given DOM element including a text input and suggestions */
  attach($element) {
    const $input = $element.querySelector('input[type="text"]');
    const $suggestions = $element.querySelector('.suggestions');

    let debounce = null;
    $input.addEventListener('input', e => {
      if (debounce) clearTimeout(debounce);

      debounce = setTimeout(() => {
        const query = $input.value.trim();
        if (query.length < 2) {
          $element.classList.remove('suggesting');
          return;
        }

        this.search(query).then(suggestions => {
          const pattern = new RegExp(`(${query})`, 'i');
          const encoded = encodeURIComponent(query);

          let list = '';
          for (const suggestion of suggestions) {
            list += `<li role="option" aria-selected="false">
              <a class="${suggestion.kind}" href="${suggestion.link}">
                <span class="title"><span class="query">${this.html(suggestion.title).replace(pattern, '<em>$1</em>')}</span></span>
                <span class="locations">${this.html(suggestion.at.join(' / ')).replace(pattern, '<em>$1</em>')}</span>
                <span class="date">${suggestion.date}</span>
              </a>
            </li>`;
          }

          // Show default option at end of search
          if (this.default) {
            list += `<li role="option" aria-selected="false">
              <a class="fulltext" href="${this.default.link.replace('%s', encoded)}">
                <span class="title">${this.default.text.replace('%s', '<span class="query"><em>' + this.html(query) + '</em></span>')}</span>
              </a>
            </li>`;
          }

          if ($suggestions.innerHTML = list) {
            $element.classList.add('suggesting');
          } else {
            $element.classList.remove('suggesting');
          }
        });
      }, this.delay);
    });
    $input.addEventListener('blur', e => {
      setTimeout(() => $element.classList.remove('suggesting'), this.delay);
    });
    $input.addEventListener('focus', e => {
      if ($suggestions.innerHTML) {
        $element.classList.add('suggesting');
      }
    });

    // Select suggestions by key
    const select = function($target) {
      if (null === $target) return;

      $target.ariaSelected = true;
      $input.value = $target.querySelector('a .query').innerText;
    };
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
}