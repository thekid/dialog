class Lightbox {
  #replace = null;
  #formats = {
    duration: value => {
      const parts = [];
      let seconds = parseInt(value);

      if (seconds >= 3600) {
        parts.push(Math.floor(seconds / 3600));
        seconds %= 3600;
      }

      parts.push(Math.floor(seconds / 60));
      parts.push(seconds % 60);
      return parts.map(part => part < 10 ? '0' + part : part).join(':');
    },
  };

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
    $meta.querySelectorAll('output').forEach($o => $o.value = $o.dataset['format']
      ? this.#formats[$o.dataset['format']]($item.dataset[$o.name])
      : $item.dataset[$o.name]
    );

    // Load image, then replace by larger version after a short duration
    $target.dataset.offset = offset;
    if ($item instanceof HTMLImageElement) {
      $meta.className = 'meta for-image';
      $display.className = 'display is-image';
      $display.innerHTML= `<img src="${$item.src}" width="100%">`;
      this.#replace = setTimeout(() => $display.querySelector('img').src = $item.dataset['full'], 150);
    } else {
      $meta.className = 'meta for-video';
      $display.className = 'display is-video loading';
      $display.innerHTML = `<video playsinline preload="metadata" poster="${$item.poster}" width="100%">${$item.innerHTML}</video>`;

      // Simulate :playing pseudo-class
      const $video = $display.querySelector('video');
      $video.addEventListener('progress', e => $display.classList.remove('loading'));
      $video.addEventListener('play', e => $display.classList.add('playing'));
      $video.addEventListener('pause', e => $display.classList.remove('playing'));
      $video.addEventListener('ended', e => $display.classList.remove('playing'));
      this.#replace = null;
    }

    // Exchange images
    $target.querySelector('.prev').src = this.#preview($media, offset - 1);
    $target.querySelector('.next').src = this.#preview($media, offset + 1);
    $target.focus();
  }

  /** Activate a target. For videos, this toggles it being played */
  #activate($target) {
    const $video = $target.querySelector('video');
    if ($video) {
      $video.paused ? $video.play() : $video.pause();
    }
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
      switch (e.code) {
        case 'Home': this.#open($target, selector, 0); break;
        case 'End': this.#open($target, selector, selector.length - 1); break;
        case 'ArrowLeft': this.#open($target, selector, parseInt($target.dataset.offset) - 1); break;
        case 'ArrowRight': this.#open($target, selector, parseInt($target.dataset.offset) + 1); break;
        case 'Space': this.#activate($target); break;
        default: return;
      }

      e.preventDefault();
      e.stopPropagation();
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

      if (Math.abs(width) <= Math.abs(height)) {
        // Swipe was mostly vertical, ignore
      } else if (width < -threshold || width > threshold) {
        this.#open($target, selector, parseInt($target.dataset.offset) - Math.sign(width));
      }
    });

    $target.querySelector('.display').addEventListener('click', e => {
      e.stopPropagation();
      this.#activate($target);
    });
    $target.addEventListener('close', e => {
      $target.querySelector('video')?.pause();
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