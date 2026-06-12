(function (Drupal, once) {
  Drupal.behaviors.imsPopupBehavior = {
    attach: function (context, settings) {
      once('imsPopupBehavior', '#ims_popup', context).forEach(function () {
        const popupElement = document.getElementById('ims_popup');
        const closeButton = document.getElementById('ims_popup__close');
        const popupTimer = popupElement.dataset.popupTimer * 1000;
        const popupStateKey = popupElement.dataset.popupRepeat;
        console.log(popupStateKey);

        setTimeout(function () {
          if (popupElement) {
            if (popupStateKey === '1') {
              popupElement.classList.add('is_visible');
            } else if (popupStateKey === '0') {
              if (!sessionStorage.getItem('popupState')) {
                popupElement.classList.add('is_visible');
                sessionStorage.setItem('popupState', 'shown');
              }
            }
          }
        }, popupTimer);

        if (closeButton && popupElement) {
          closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            popupElement.classList.remove('is_visible');
          });
        }
      });
    }
  };
})(Drupal, once);
