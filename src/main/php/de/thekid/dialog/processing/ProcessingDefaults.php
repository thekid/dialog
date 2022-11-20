<?php namespace de\thekid\dialog\processing;

trait ProcessingDefaults {

  private function files(): Files {
    return new Files()
      ->matching(['.jpg', '.jpeg', '.png', '.webp'], new Images()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
        ->targeting('full', new ResizeTo(3840, 'webp'))
      )
      ->matching(['.mp4', '.mpeg', '.mov'], new Videos()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
      )
    ;
  }
}