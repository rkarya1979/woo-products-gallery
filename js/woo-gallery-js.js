const wpgbuttons = document.querySelectorAll(`a[data-modal-trigger]`);

for(let button of wpgbuttons) {
  wpgmodalEvent(button);
}

function wpgmodalEvent(button) {
  button.addEventListener('click', () => {
    const trigger = button.getAttribute('data-modal-trigger');
    const modal = document.querySelector(`[data-modal=${trigger}]`);
    const contentWrapper = modal.querySelector('.content-wrapper');
    const close = modal.querySelector('.close');

    close.addEventListener('click', () => modal.classList.remove('open'));
    modal.addEventListener('click', () => modal.classList.remove('open'));
    contentWrapper.addEventListener('click', (e) => e.stopPropagation());

    modal.classList.toggle('open');
  });
}