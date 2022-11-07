class Lightbox {

  /** Opens the given lightbox, loading the image and filling in meta data */
  #open($target, $link) {
    const $full = $target.querySelector('img');
    $full.src = '';
    $target.classList.add('open');
    $full.src = $link.href;

    // Overlay meta data if present
    const $img = $link.querySelector('img');
    const $meta = $target.querySelector('.meta');
    if ('' !== ($img.dataset.make ?? '')) {
      $meta.querySelectorAll('output').forEach($o => $o.value = $img.dataset[$o.name]);
      $meta.style.visibility = 'visible';
    } else {
      $meta.style.visibility = 'hidden';
    }
  }

  /** Closes the given lightbox */
  #close($target) {
    $target.classList.remove('open');
  }

  /** Attach all of the given elements to open the lightbox specified by the given DOM element */
  attach(selector, $target) {
    document.addEventListener('keydown', e => {
      if ('Escape' === e.key) this.#close($target);
    });

    $target.addEventListener('click', e => {
      this.#close($target);
    });

    selector.forEach($link => $link.addEventListener('click', e => {
      e.preventDefault();
      this.#open($target, $link);
    }));
  }
}