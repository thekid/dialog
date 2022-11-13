class Lightbox {

  /** Opens the given lightbox, loading the image and filling in meta data */
  #open($target, $link) {
    const $full = $target.querySelector('img');
    const $img = $link.querySelector('img');

    // Use opening image...
    $full.src = $img.src;
    $target.showModal();

    // Overlay meta data if present
    const $meta = $target.querySelector('.meta');
    if ('' !== ($img.dataset.make ?? '')) {
      $meta.querySelectorAll('output').forEach($o => $o.value = $img.dataset[$o.name]);
      $meta.style.visibility = 'visible';
    } else {
      $meta.style.visibility = 'hidden';
    }

    // ...then replace by larger version
    $full.src = $link.href;
  }

  /** Attach all of the given elements to open the lightbox specified by the given DOM element */
  attach(selector, $target) {
    $target.addEventListener('click', e => $target.close());
    selector.forEach($link => $link.addEventListener('click', e => {
      e.preventDefault();
      this.#open($target, $link);
    }));
  }
}