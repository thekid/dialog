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

  /** Returns a preview image for the given media item */
  #preview($media, offset) {
    const $item = $media.item(this.#wrap(offset, $media.length));
    if ($item instanceof HTMLImageElement) {
      return $item.src;
    } else if ($item instanceof HTMLVideoElement) {
      return $item.poster;
    }
  }

  /** Opens the given lightbox, loading the image and filling in meta data */
  #open($target, $media, offset) {
    offset = this.#wrap(offset, $media.length);
    const $display = $target.querySelector('.display');
    const $item = $media.item(offset);
    const $meta = $target.querySelector('.meta');

    // Update meta information
    $meta.querySelectorAll('output').forEach($o => $o.value = $item.dataset[$o.name]);

    // Load image, then replace by larger version after a short duration
    $target.dataset.offset = offset;
    if ($item instanceof HTMLImageElement) {
      $meta.className = 'meta for-image';
      $display.innerHTML= `<img src="${$item.src}" width="100%">`;
      this.#replace = setTimeout(() => $display.querySelector('img').src = $item.dataset['full'], 150);
    } else {
      $meta.className = 'meta for-video';
      $display.innerHTML = `<video autoplay playsinline poster="${$item.poster}" width="100%">${$item.innerHTML}</video>`;
      this.#replace = null;
    }

    // Exchange images
    $target.querySelector('.prev').src = this.#preview($media, offset - 1);
    $target.querySelector('.next').src = this.#preview($media, offset + 1);
    $target.focus();
  }

  /** Attach all of the given elements to open the lightbox specified by the given DOM element */
  attach(selector, $target) {
    $target.addEventListener('click', e => {
      $target.close();
    });

    // Keyboard navigation
    $target.addEventListener('keydown', e => {
      this.#replace && clearTimeout(this.#replace);

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
      this.#replace && clearTimeout(this.#replace);
      x = e.touches[0].clientX;
      y = e.touches[0].clientY;
    });
    $target.addEventListener('touchmove', e => {
      const delta = e.changedTouches[0].clientX - x;
      $target.querySelector('.display').style.transform = `translate(${delta}px, 0)`;
      if (delta > 0) {
        $target.querySelector('.next').style.visibility = null;
        $target.querySelector('.prev').style.visibility = 'visible';
      } else {
        $target.querySelector('.next').style.visibility = 'visible';
        $target.querySelector('.prev').style.visibility = null;
      }
      e.cancelable && e.preventDefault();
    }, { passive: false });
    $target.addEventListener('touchend', e => {
      const width = e.changedTouches[0].clientX - x;
      const height = e.changedTouches[0].clientY - y;
      $target.querySelector('.prev').style.visibility = null;
      $target.querySelector('.next').style.visibility = null;
      $target.querySelector('.display').style.transform = null;

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

    $target.addEventListener('close', e => {
      const $media = $target.querySelector('.display').children[0];
      if ($media instanceof HTMLVideoElement) {
        $media.pause();
      }
    });

    let i = 0;
    for (const $item of selector) {
      const offset = i++;
      if ($item instanceof HTMLImageElement) {
        $item.addEventListener('click', e => {
          e.preventDefault();
          $target.showModal();
          this.#open($target, selector, offset);
        });
      }
    }
  }
}