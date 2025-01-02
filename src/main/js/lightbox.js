class Lightbox {

  #meta($meta, dataset) {
    if ('' !== (dataset.make ?? '')) {
      $meta.querySelectorAll('output').forEach($o => $o.value = dataset[$o.name]);
      $meta.style.visibility = 'visible';
    } else {
      $meta.style.visibility = 'hidden';
    }
  }

  /** Opens the given lightbox, loading the image and filling in meta data */
  #open($target, $link, offset) {
    const $full = $target.querySelector('img');
    const $img = $link.querySelector('img');

    // Use opening image...
    $full.src = $img.src;
    $target.showModal();

    // ...then replace by larger version
    this.#meta($target.querySelector('.meta'), $img.dataset);
    $target.dataset.offset = offset;
    $full.src = $link.href;
  }

  #navigate($target, $link, offset) {
    this.#meta($target.querySelector('.meta'), $link.querySelector('img').dataset);
    $target.dataset.offset = offset;
    $target.querySelector('img').src = $link.href;
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
        case 'Home': offset = 0; break;
        case 'End': offset = selector.length - 1; break;
        case 'ArrowLeft': offset = parseInt($target.dataset.offset) - 1; break;
        case 'ArrowRight': offset = parseInt($target.dataset.offset) + 1; break;
        default: return;
      }

      e.stopPropagation();
      if (offset >= 0 && offset < selector.length) {
        this.#navigate($target, selector.item(offset), offset);
      }
    });

    // Swipe left and right
    let x, y;
    const threshold = 50;
    $target.addEventListener('touchstart', e => {
      x = e.touches[0].clientX;
      y = e.touches[0].clientY;
    });
    $target.addEventListener('touchmove', e => {
      $target.querySelector('img').style.transform = `translate(${e.changedTouches[0].clientX - x}px, 0)`;
      e.cancelable && e.preventDefault();
    }, { passive: false });
    $target.addEventListener('touchend', e => {
      const width = e.changedTouches[0].clientX - x;
      const height = e.changedTouches[0].clientY - y;
      $target.querySelector('img').style.transform = null;

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
      if (offset >= 0 && offset < selector.length) {
        this.#navigate($target, selector.item(offset), offset);
      }
    });

    let i = 0;
    for (const $link of selector) {
      const offset = i++;
      $link.addEventListener('click', e => {
        e.preventDefault();
        this.#open($target, $link, offset);
      });
    }
  }
}