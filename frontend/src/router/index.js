import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Layouts
import LandingView from '@/views/LandingView.vue';
import OubsView from '@/views/OubsView.vue';
import RecipientView from '@/views/RecipientView.vue';

// Landing Pages
import RecipientSelector from '@/components/landing/RecipientSelector.vue';

// OUBS Pages
import OubsLogin from '@/components/oubs/OubsLogin.vue';
import OubsHome from '@/components/oubs/OubsHome.vue';
import DocumentManager from '@/components/oubs/DocumentManager.vue';
import ArchivedDocuments from '@/components/oubs/ArchivedDocuments.vue';
import UploadDocument from '@/components/oubs/UploadDocument.vue';
import SummaryView from '@/components/oubs/SummaryView.vue';
import UserList from '@/components/oubs/UserList.vue';
import OubsLogs from '@/components/oubs/OubsLogs.vue';

// Recipient Pages
import RecipientLogin from '@/components/recipients/RecipientLogin.vue';
import RecipientDashboard from '@/components/recipients/RecipientHome.vue';
import DocumentList from '@/components/recipients/DocumentList.vue';
import ReplySlipForm from '@/components/recipients/ReplySlipForm.vue';
import MyReplies from '@/components/recipients/MyReplies.vue';
import RecipientHome from '@/components/recipients/RecipientHome.vue';
import RecipientSignatureSetup from '@/components/recipients/RecipientSignatureSetup.vue';
import DocumentReview from '@/components/recipients/DocumentReview.vue';


const routes = [
  {
    path: '/',
    name: 'Landing',
    component: LandingView,
    meta: { requiresGuest: true }
  },
  
  // OUBS Routes
  {
    path: '/oubs',
    component: OubsView,
    meta: { requiresAuth: true, userType: 'oubs' },
    children: [
      {
        path: 'home',
        name: 'OubsHome',
        component: OubsHome
      },
      {
        path: 'documents',
        name: 'OubsDocuments',
        component: DocumentManager
      },
      {
        path: 'archived',
        name: 'ArchivedDocuments',
        component: ArchivedDocuments
      },
      {
        path: 'upload',
        name: 'UploadDocument',
        component: UploadDocument
      },
      {
        path: 'summary/:id?',
        name: 'SummaryView',
        component: SummaryView,
        props: true
      },
      {
        path: 'users',
        name: 'OubsUsers',
        component: UserList
      },
      {
        path: 'logs',
        name: 'OubsLogs',
        component: OubsLogs
      }
    ]
  },
  {
    path: '/oubs/login',
    name: 'OubsLogin',
    component: OubsLogin,
    meta: { requiresGuest: true }
  },
  
  // Recipient Routes
  {
    path: '/recipient',
    component: RecipientView,
    meta: { requiresAuth: true, userType: ['bor', 'uac', 'uadmin'] },
    children: [
      {
        path: 'home',
        name: 'RecipientHome',
        component: RecipientHome
      },
      {
        path: 'signature-setup',
        name: 'RecipientSignatureSetup',
        component: RecipientSignatureSetup
      },
      {
        path: 'documents',
        name: 'RecipientDocuments',
        component: DocumentList
      },
      {
        path: 'documents/:id',
        name: 'RecipientDocumentDetail',
        component: DocumentList,
        props: true
      },
      {
        path: 'reply/:id',
        name: 'ReplySlip',
        component: ReplySlipForm,
        props: true
      },
      {
        path: 'my-replies',
        name: 'MyReplies',
        component: MyReplies
      },
      {
        path: 'document-review',
        name: 'DocumentReview',
        component: DocumentReview
      }
    ]
  },
  {
    path: '/recipient/select',
    name: 'RecipientSelect',
    component: RecipientSelector,
    meta: { requiresGuest: true }
  },
  {
    path: '/recipient/login/:type',
    name: 'RecipientLogin',
    component: RecipientLogin,
    props: true,
    meta: { requiresGuest: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/views/NotificationCenter.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const userType = authStore.user?.user_type;
  const isRecipient = ['bor', 'uac', 'uadmin'].includes(userType);
  const hasSignature = !!String(authStore.user?.signature_image || '').trim();

  // Check if route requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/');
    return;
  }

  // Check user type for protected routes
  if (to.meta.userType && isAuthenticated) {
    const allowedTypes = Array.isArray(to.meta.userType) 
      ? to.meta.userType 
      : [to.meta.userType];
    
    if (!allowedTypes.includes(userType)) {
      // Redirect based on user type
      if (userType === 'oubs') {
        next('/oubs/home');
      } else {
        next('/recipient/home');
      }
      return;
    }
  }

  if (isAuthenticated && isRecipient) {
    const isSignatureSetupRoute = to.name === 'RecipientSignatureSetup';
    if (!hasSignature && !isSignatureSetupRoute) {
      next('/recipient/signature-setup');
      return;
    }
    if (hasSignature && isSignatureSetupRoute) {
      next('/recipient/home');
      return;
    }
  }

  // Redirect authenticated users away from guest pages
  if (to.meta.requiresGuest && isAuthenticated) {
    if (userType === 'oubs') {
      next('/oubs/home');
    } else {
      next('/recipient/home');
    }
    return;
  }

  next();
});

export default router;
