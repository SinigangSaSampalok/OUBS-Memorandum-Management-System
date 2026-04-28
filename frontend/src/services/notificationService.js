import { useNotificationStore } from '@/stores/notification'
import { useNotifications } from '@/composables/useNotifications'

/**
 * Notification Service
 * Centralized place for creating notifications throughout the application
 */

export const notificationService = {
  /**
   * Create a welcome notification (privacy notice)
   * Shown to new accounts after setting e-signature
   */
  createWelcomeNotification: async (userId) => {
    // Notifications are created and fetched from backend
    // This is kept for reference
  },

  /**
   * Create a new document notification
   * Shown when a new document is uploaded for reply or viewing
   */
  createNewDocumentNotification: async (documentData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'document_new',
      title: 'New Document',
      message: `A new document has been uploaded: ${documentData.title}. Deadline: ${new Date(documentData.deadline).toLocaleDateString()}`,
      data: documentData,
      action_url: `/recipient/documents/${documentData.id}`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a password reset request notification
   * Shown to OUBS admin when recipient requests password reset
   */
  createPasswordResetNotification: async (requestData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'reset_request',
      title: 'Password Reset Request',
      message: `${requestData.member_name} has requested a password reset.`,
      data: requestData,
      action_url: `/oubs/users`, // Link to password reset management
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a document closed notification
   */
  createDocumentClosedNotification: async (documentData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'document_closed',
      title: 'Document Closed',
      message: `The document "${documentData.title}" has been closed.`,
      data: documentData,
      action_url: `/recipient/documents/${documentData.id}`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a document for review notification
   * Shown to BOR reviewers
   */
  createDocumentReviewNotification: async (documentData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'document_review',
      title: 'Document Review Required',
      message: `A document requires your review: ${documentData.title}`,
      data: documentData,
      action_url: `/recipient/document-review`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a document deadline approaching notification
   */
  createDocumentDeadlineNotification: async (documentData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const daysLeft = Math.ceil((new Date(documentData.deadline) - new Date()) / (1000 * 60 * 60 * 24))

    const notification = {
      type: 'document_deadline',
      title: 'Deadline Approaching',
      message: `"${documentData.title}" has ${daysLeft} days remaining. Reply deadline: ${new Date(documentData.deadline).toLocaleDateString()}`,
      data: documentData,
      action_url: `/recipient/documents/${documentData.id}`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a document completed notification
   * Shown to OUBS for documents that are completed
   */
  createDocumentCompletedNotification: async (documentData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'document_completed',
      title: 'Document Completed',
      message: `Document "${documentData.title}" has been completed and closed.`,
      data: documentData,
      action_url: `/oubs/documents`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Create a successful reply notification
   * Shown to recipients when their reply is successfully submitted
   */
  createReplySuccessNotification: async (replyData) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'reply_success',
      title: 'Reply Submitted',
      message: `Your reply has been successfully submitted for: ${replyData.document_title}`,
      data: replyData,
      action_url: `/recipient/my-replies`,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Generic error notification
   */
  createErrorNotification: async (message, data = {}) => {
    const { showToast } = useNotifications()

    const notification = {
      type: 'error',
      title: 'Error',
      message,
      data,
    }

    showToast(notification)
  },

  /**
   * Generic success notification
   */
  createSuccessNotification: async (title, message, actionUrl = null, data = {}) => {
    const store = useNotificationStore()
    const { showToast } = useNotifications()

    const notification = {
      type: 'success',
      title,
      message,
      data,
      action_url: actionUrl,
    }

    await store.addLocalNotification(notification)
    showToast(notification)
  },

  /**
   * Generic info notification
   */
  createInfoNotification: async (title, message, actionUrl = null, data = {}) => {
    const { showToast } = useNotifications()

    const notification = {
      type: 'info',
      title,
      message,
      data,
      action_url: actionUrl,
    }

    showToast(notification)
  },
}
