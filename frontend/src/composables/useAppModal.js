import { reactive } from 'vue';

const modalState = reactive({
  open: false,
  type: 'info',
  title: '',
  message: '',
  confirmText: 'OK',
  cancelText: 'Cancel',
  showCancel: false,
  resolver: null,
});

const closeModal = (result = false) => {
  if (modalState.resolver) {
    modalState.resolver(result);
  }
  modalState.open = false;
  modalState.resolver = null;
};

const openModal = ({
  type = 'info',
  title = '',
  message = '',
  confirmText = 'OK',
  cancelText = 'Cancel',
  showCancel = false,
}) => {
  modalState.type = type;
  modalState.title = title;
  modalState.message = message;
  modalState.confirmText = confirmText;
  modalState.cancelText = cancelText;
  modalState.showCancel = showCancel;
  modalState.open = true;

  return new Promise((resolve) => {
    modalState.resolver = resolve;
  });
};

export const useAppModal = () => {
  const showError = (message, title = 'Error') =>
    openModal({
      type: 'error',
      title,
      message,
      confirmText: 'Understood',
      showCancel: false,
    });

  const showSuccess = (message, title = 'Success', autoCloseMs = null) => {
    const promise = openModal({
      type: 'success',
      title,
      message,
      confirmText: 'Continue',
      showCancel: false,
    });

    if (typeof autoCloseMs === 'number' && autoCloseMs > 0) {
      setTimeout(() => {
        if (modalState.open && modalState.type === 'success') {
          closeModal(true);
        }
      }, autoCloseMs);
    }

    return promise;
  };

  const showConfirm = ({
    title = 'Confirm Action',
    message = 'Please confirm to proceed.',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
  }) =>
    openModal({
      type: 'confirm',
      title,
      message,
      confirmText,
      cancelText,
      showCancel: true,
    });

  return {
    modalState,
    closeModal,
    showError,
    showSuccess,
    showConfirm,
  };
};
