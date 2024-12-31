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
    $target.addEventListener('click', e => {
      $target.close();
    });

    // Keyboard navigation
    $target.addEventListener('keydown', e => {
      let offset;
      switch (e.key) {
        case 'ArrowLeft': offset = parseInt(e.target.dataset.offset) - 1; break;
        case 'ArrowRight': offset = parseInt(e.target.dataset.offset) + 1; break;
        default: return;
      }

      e.stopPropagation();
      selector.item(offset)?.dispatchEvent(new Event('click'));
    });

    // Swipe left and right
    let x, y;
    const threshold = 50;
    $target.addEventListener('touchstart', e => {
      x = e.touches[0].clientX;
      y = e.touches[0].clientY;
    });
    $target.addEventListener('touchmove', e => e.cancelable && e.preventDefault(), { passive: false });
    $target.addEventListener('touchend', e => {
      const width = e.changedTouches[0].clientX - x;
      const height = e.changedTouches[0].clientY - y;

      // Swipe was mostly vertical, ignore
      if (Math.abs(width) <= Math.abs(height)) return;

      let offset;
      if (width > threshold) {
        offset = parseInt($target.dataset.offset) - 1;
      } else if (width < -threshold) {
        offset = parseInt($target.dataset.offset) + 1;
      } else {
        return;
      }

      e.stopPropagation();
      selector.item(offset)?.dispatchEvent(new Event('click'));
    });

    let i = 0;
    for (const $link of selector) {
      const offset = i++;
      $link.addEventListener('click', e => {
        e.preventDefault();
        $target.dataset.offset = offset;
        this.#open($target, $link);
      });
    }
  }
}