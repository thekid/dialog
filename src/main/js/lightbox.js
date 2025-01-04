class Lightbox {
  #replace = null;

  /** Wraps around to last item at beginning, and first item at end of list */
  #wrap(offset, length) {
    if (offset < 0) {
      return length - 1;
    } else if (offset >= length) {
      return 0;
    } else {
      return offset;
    }
  }

  /** Opens the given lightbox, loading the image and filling in meta data */
  #open($target, $links, offset) {
    offset = this.#wrap(offset, $links.length);
    const $display = $target.querySelector('img.display');
    const $link = $links.item(offset);
    const $img = $link.querySelector('img');
    const $meta = $target.querySelector('.meta');

    // Update meta information
    $target.dataset.offset = offset;
    if ('' !== ($img.dataset.make ?? '')) {
      $meta.querySelectorAll('output').forEach($o => $o.value = $img.dataset[$o.name]);
      $meta.style.visibility = 'visible';
    } else {
      $meta.style.visibility = 'hidden';
    }

    // Exchange images
    $display.src = $img.src;
    $target.querySelector('img.prev').src = $links.item(this.#wrap(offset - 1, $links.length)).querySelector('img').src;
    $target.querySelector('img.next').src = $links.item(this.#wrap(offset + 1, $links.length)).querySelector('img').src;

    // ...then replace by larger version after a short duration
    this.#replace = setTimeout(() => $display.src = $link.href, 150);
  }

  /** Attach all of the given elements to open the lightbox specified by the given DOM element */
  attach(selector, $target) {
    $target.addEventListener('click', e => {
      $target.close();
    });

    // Keyboard navigation
    $target.addEventListener('keydown', e => {
      clearTimeout(this.#replace);

      let offset;
      switch (e.key) {
        case 'Home': offset = 0; break;
        case 'End': offset = selector.length - 1; break;
        case 'ArrowLeft': offset = parseInt($target.dataset.offset) - 1; break;
        case 'ArrowRight': offset = parseInt($target.dataset.offset) + 1; break;
        default: return;
      }

      e.preventDefault();
      e.stopPropagation();
      this.#open($target, selector, offset);
    });

    // Swipe left and right
    let x, y;
    const threshold = 50;
    $target.addEventListener('touchstart', e => {
      clearTimeout(this.#replace);
      x = e.touches[0].clientX;
      y = e.touches[0].clientY;
    });
    $target.addEventListener('touchmove', e => {
      const delta = e.changedTouches[0].clientX - x;
      $target.querySelector('img.display').style.transform = `translate(${delta}px, 0)`;
      if (delta > 0) {
        $target.querySelector('img.next').style.visibility = null;
        $target.querySelector('img.prev').style.visibility = 'visible';
      } else {
        $target.querySelector('img.next').style.visibility = 'visible';
        $target.querySelector('img.prev').style.visibility = null;
      }
      e.cancelable && e.preventDefault();
    }, { passive: false });
    $target.addEventListener('touchend', e => {
      const width = e.changedTouches[0].clientX - x;
      const height = e.changedTouches[0].clientY - y;
      $target.querySelector('img.prev').style.visibility = null;
      $target.querySelector('img.next').style.visibility = null;
      $target.querySelector('img.display').style.transform = null;

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
      this.#open($target, selector, offset);
    });

    let i = 0;
    for (const $link of selector) {
      const offset = i++;
      $link.addEventListener('click', e => {
        e.preventDefault();
        $target.showModal();
        this.#open($target, selector, offset);
      });
    }
  }
}