<template>
  <section class="manager-screen">
    <header class="manager-hero">
      <p class="hero-kicker">OUBS PORTAL</p>
      <h1>User List</h1>
      <p class="hero-subtitle">All recipients except OUBS users.</p>
    </header>

    <article class="manager-card">
      <div class="manager-toolbar">
        <div class="tabs" role="tablist" aria-label="User list mode">
          <button
            type="button"
            class="tab-btn"
            :class="activeTab === 'users' && 'tab-btn-active'"
            @click="activeTab = 'users'"
          >
            Users
          </button>
          <button
            type="button"
            class="tab-btn"
            :class="activeTab === 'add' && 'tab-btn-active'"
            @click="activeTab = 'add'"
          >
            Add User
          </button>
          <button
            type="button"
            class="tab-btn"
            :class="activeTab === 'requests' && 'tab-btn-active'"
            @click="activeTab = 'requests'"
          >
            Password Requests
          </button>
        </div>

        <button
          type="button"
          class="refresh-btn"
          :disabled="isLoading"
          @click="fetchUsers"
        >
          {{ isLoading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <div class="manager-card-body pt-2 pb-4">
        <div v-if="activeTab === 'users'">
          <div class="filter-section">
            <div class="filter-group">
              <label class="filter-label">Search</label>
              <div class="search-input-wrapper">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input
                  v-model="searchTerm"
                  type="text"
                  class="search-input"
                  placeholder="Search by name, email, position..."
                />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <div>
              <label class="block text-xs font-semibold text-white uppercase tracking-wide mb-1">Recipient Group</label>
              <select
                v-model="selectedGroup"
                class="w-full rounded-lg border border-amber-300 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400"
              >
                <option value="all">All Recipients</option>
                <option value="bor">Board of Regents</option>
                <option value="uac">Academic Council</option>
                <option value="uadmin">Administrative Council</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-white uppercase tracking-wide mb-1">Order</label>
              <select
                v-model="sortOrder"
                class="w-full rounded-lg border border-amber-300 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400"
              >
                <option value="asc">Alphabetical (A-Z)</option>
                <option value="desc">Alphabetical (Z-A)</option>
              </select>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'add'" class="add-user-section">
          <form class="upload-form" @submit.prevent="addUser">
            <div class="form-grid">
              <div class="field">
                <label class="field-label" for="fullName">Full Name *</label>
                <input
                  id="fullName"
                  v-model.trim="addForm.full_name"
                  type="text"
                  class="field-input"
                  placeholder="Enter full name"
                  required
                />
              </div>
              <div class="field">
                <label class="field-label" for="recipientGroup">Recipient Group *</label>
                <select
                  id="recipientGroup"
                  v-model="addForm.user_type"
                  class="field-input"
                  required
                >
                  <option disabled value="">Select recipient group</option>
                  <option value="bor">Board of Regents</option>
                  <option value="uac">Academic Council</option>
                  <option value="uadmin">Administrative Council</option>
                </select>
              </div>
            </div>

            <div class="form-grid">
              <div class="field">
                <label class="field-label" for="position">Position</label>
                <input
                  id="position"
                  v-model.trim="addForm.position"
                  type="text"
                  class="field-input"
                  placeholder="Enter position (optional)"
                />
              </div>
              <div v-if="addForm.user_type === 'uac'" class="field">
                <label class="field-label" for="collegeCampus">College/Campus *</label>
                <select
                  id="collegeCampus"
                  v-model.number="addForm.college_campus_id"
                  class="field-input"
                  required
                >
                  <option :value="null" disabled>Select college/campus</option>
                  <option v-for="entry in collegeCampuses" :key="entry.id" :value="entry.id">
                    {{ entry.name }}
                  </option>
                </select>
              </div>
            </div>

            <p class="field-help field-help-emerald">
              New recipients are created without a password. They can set their password on first access.
            </p>

            <div class="actions">
              <button type="submit" class="btn btn-primary" :disabled="isAddingUser">
                {{ isAddingUser ? 'Adding...' : 'Add User' }}
              </button>
              <button type="button" class="btn btn-secondary" :disabled="isAddingUser" @click="resetAddForm">
                Clear
              </button>
            </div>
          </form>
        </div>

        <div v-else class="flex items-center">
          <p class="text-sm text-gray-600">Review recipient password reset requests.</p>
        </div>

        <div v-if="errorMessage" class="text-sm text-red-600 mb-4 pt-4">
          {{ errorMessage }}
        </div>

        <div v-if="isLoading" class="text-gray-600 py-6 mt-2">Loading users...</div>

        <div v-if="!isLoading && activeTab === 'users'" class="pb-6 mt-2">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Recipient Group</th>
                  <th>Position</th>
                  <th v-if="showCollegeCampusColumn">College/Campus</th>
                  <th class="actions-header">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredUsers.length === 0">
                  <td :colspan="showCollegeCampusColumn ? 5 : 4" class="empty-cell">
                    No users found.
                  </td>
                </tr>
                <tr
                  v-for="user in filteredUsers"
                  :key="`${user.user_type}-${user.id}`"
                  :class="!user.has_password ? 'row-no-password' : ''"
                >
                  <td class="doc-cell">
                    <input
                      v-if="editUserId === user.id"
                      v-model.trim="editForm.full_name"
                      type="text"
                      :class="!user.has_password ? 'input-no-password' : ''"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-amber-400"
                    />
                    <span v-else class="doc-title">{{ user.full_name }}</span>
                  </td>
                  <td>{{ formatRecipient(user.user_type) }}</td>
                  <td>
                    <input
                      v-if="editUserId === user.id"
                      v-model.trim="editForm.position"
                      type="text"
                      :class="!user.has_password ? 'input-no-password' : ''"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-amber-400"
                    />
                    <span v-else>{{ user.position || '-' }}</span>
                  </td>
                  <td v-if="showCollegeCampusColumn">
                    <template v-if="user.user_type === 'uac'">
                      <select
                        v-if="editUserId === user.id"
                        v-model.number="editForm.college_campus_id"
                        :class="!user.has_password ? 'input-no-password' : ''"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-amber-400"
                      >
                        <option value="" disabled>Select college/campus</option>
                        <option v-for="entry in collegeCampuses" :key="entry.id" :value="entry.id">
                          {{ entry.name }}
                        </option>
                      </select>
                      <span v-else>{{ user.college_campus_name || '-' }}</span>
                    </template>
                    <span v-else>-</span>
                  </td>
                  <td class="actions-cell">
                    <div class="flex items-center justify-center gap-2">
                      <template v-if="editUserId === user.id">
                        <button
                          class="h-8 min-w-16 px-2 text-xs rounded-lg border border-green-300 text-green-700 hover:bg-green-50 disabled:opacity-60"
                          :disabled="actionLoadingId === user.id"
                          @click="saveUser(user)"
                        >
                          Save
                        </button>
                        <button
                          class="h-8 min-w-16 px-2 text-xs rounded-lg border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-60"
                          :disabled="actionLoadingId === user.id"
                          @click="cancelEdit"
                        >
                          Cancel
                        </button>
                      </template>
                      <template v-else>
                        <button
                          class="h-8 min-w-16 px-2 text-xs rounded-lg border border-blue-300 text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                          :disabled="actionLoadingId === user.id"
                          @click="startEdit(user)"
                        >
                          Edit
                        </button>
                        <button
                          v-if="user.user_type === 'bor' && (!hasDocumentReviewer || Number(user.is_document_reviewer ?? 0) === 1)"
                          class="h-8 min-w-24 px-2.5 text-xs rounded-lg border disabled:opacity-60"
                          :class="Number(user.is_document_reviewer ?? 0) === 1
                            ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100'
                            : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'"
                          :disabled="actionLoadingId === user.id"
                          @click="Number(user.is_document_reviewer ?? 0) === 1 ? unsetDocumentReviewer(user) : assignDocumentReviewer(user)"
                          :title="Number(user.is_document_reviewer ?? 0) === 1 ? 'Unset document reviewer' : 'Assign as document reviewer'"
                        >
                          {{ Number(user.is_document_reviewer ?? 0) === 1 ? 'Unset Reviewer' : 'Set Reviewer' }}
                        </button>
                        <button
                          class="h-8 min-w-16 px-2 text-xs rounded-lg border border-red-300 text-red-700 hover:bg-red-50 disabled:opacity-60"
                          :disabled="actionLoadingId === user.id"
                          @click="deleteUser(user)"
                        >
                          Delete
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="!isLoading && activeTab === 'requests'" class="pb-6 pt-2 mt-2">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Group</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Reviewed By</th>
                  <th class="actions-header">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="pendingResetRequests.length === 0">
                  <td colspan="6" class="empty-cell">No pending password reset requests.</td>
                </tr>
                <tr v-for="request in pendingResetRequests" :key="request.id">
                  <td class="doc-cell">
                    <div class="doc-title">{{ request.full_name }}</div>
                  </td>
                  <td>{{ formatRecipient(request.user_type) }}</td>
                  <td>{{ request.reason || '-' }}</td>
                  <td>
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border"
                      :class="statusClass(request.status)"
                    >
                      {{ request.status }}
                    </span>
                  </td>
                  <td>{{ request.reviewed_by_name || '-' }}</td>
                  <td class="actions-cell">
                    <div class="flex items-center gap-2">
                      <button
                        class="h-8 min-w-18 px-2.5 text-xs rounded-lg border border-green-300 text-green-700 hover:bg-green-50 disabled:opacity-60"
                        :disabled="reviewLoadingId === request.id"
                        @click="reviewRequest(request, 'approve')"
                      >
                        Approve
                      </button>
                      <button
                        class="h-8 min-w-18 px-2.5 text-xs rounded-lg border border-red-300 text-red-700 hover:bg-red-50 disabled:opacity-60"
                        :disabled="reviewLoadingId === request.id"
                        @click="reviewRequest(request, 'reject')"
                      >
                        Reject
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { documentReviewService, memberService, passwordResetService } from '@/services/api';
import { useAppModal } from '@/composables/useAppModal';

const users = ref([]);
const activeTab = ref('users');
const isLoading = ref(false);
const errorMessage = ref('');
const searchTerm = ref('');
const selectedGroup = ref('all');
const sortOrder = ref('asc');
const resetRequests = ref([]);
const reviewLoadingId = ref(null);
const editUserId = ref(null);
const actionLoadingId = ref(null);
const isAddingUser = ref(false);
const collegeCampuses = ref([]);
const editForm = ref({
  full_name: '',
  position: '',
  college_campus_id: null,
});
const addForm = ref({
  full_name: '',
  user_type: 'bor',
  position: '',
  college_campus_id: null,
});
const { showError, showSuccess, showConfirm } = useAppModal();

const recipientMap = {
  bor: 'Board of Regents',
  uac: 'Academic Council',
  uadmin: 'Administrative Council',
};

const formatRecipient = (type) => recipientMap[type] || 'Unknown';

const statusClass = (status) => {
  const normalized = `${status || 'pending'}`.toLowerCase();
  if (normalized === 'approved' || normalized === 'completed') return 'bg-green-50 text-green-700 border-green-200';
  if (normalized === 'disapproved' || normalized === 'rejected') return 'bg-red-50 text-red-700 border-red-200';
  return 'bg-yellow-50 text-yellow-700 border-yellow-200';
};

const compareValues = (left, right) => {
  const a = `${left ?? ''}`.toLowerCase();
  const b = `${right ?? ''}`.toLowerCase();
  if (a < b) return -1;
  if (a > b) return 1;
  return 0;
};

const normalizeWords = (value) => `${value ?? ''}`
  .toLowerCase()
  .replace(/[^a-z0-9\s]/g, ' ')
  .split(/\s+/)
  .filter(Boolean);

const filteredUsers = computed(() => {
  const groupFiltered = selectedGroup.value === 'all'
    ? [...users.value]
    : users.value.filter((user) => user.user_type === selectedGroup.value);

  const queryWords = normalizeWords(searchTerm.value);
  const list = queryWords.length
    ? groupFiltered.filter((user) => {
      const searchableWords = normalizeWords([
        user.full_name,
        user.position,
        user.college_campus_name,
        formatRecipient(user.user_type),
      ].join(' '));

      return queryWords.every((query) =>
        searchableWords.some((word) => word.startsWith(query))
      );
    })
    : groupFiltered;

  return list.sort((a, b) => {
    const result = compareValues(a.full_name, b.full_name);
    return sortOrder.value === 'asc' ? result : -result;
  });
});

const pendingResetRequests = computed(() =>
  resetRequests.value.filter((request) => request.status === 'pending')
);

const showCollegeCampusColumn = computed(() => selectedGroup.value === 'uac');

const hasDocumentReviewer = computed(() =>
  users.value.some((user) => user.user_type === 'bor' && Number(user.is_document_reviewer ?? 0) === 1)
);

const fetchUsers = async () => {
  errorMessage.value = '';
  isLoading.value = true;

  try {
    const [borResponse, uacResponse, uadminResponse] = await Promise.all([
      memberService.byType('bor'),
      memberService.byType('uac'),
      memberService.byType('uadmin'),
    ]);

    const borUsers = (borResponse?.data || []).map((member) => ({
      ...member,
      user_type: 'bor',
    }));

    const uacUsers = uacResponse?.data || [];
    const uadminUsers = uadminResponse?.data || [];

    users.value = [...borUsers, ...uacUsers, ...uadminUsers];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load users.';
    await showError(errorMessage.value, 'Load Failed');
  } finally {
    isLoading.value = false;
  }
};

const fetchResetRequests = async () => {
  try {
    const response = await passwordResetService.list();
    resetRequests.value = response?.data || [];
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to load password reset requests.';
    await showError(errorMessage.value, 'Load Failed');
  }
};

const reviewRequest = async (request, action) => {
  const confirmed = await showConfirm({
    title: action === 'approve' ? 'Approve Request' : 'Reject Request',
    message: `Are you sure you want to ${action} the password reset request for ${request.full_name}?`,
    confirmText: action === 'approve' ? 'Approve' : 'Reject',
    cancelText: 'Cancel',
  });
  if (!confirmed) return;

  reviewLoadingId.value = request.id;
  try {
    await passwordResetService.review(request.id, {
      action,
      note: '',
    });
    await fetchResetRequests();
    await fetchUsers();
    await showSuccess(`Request ${action}d successfully.`, 'Review Complete');
  } catch (error) {
    errorMessage.value = error?.message || `Failed to ${action} request.`;
    await showError(errorMessage.value, 'Review Failed');
  } finally {
    reviewLoadingId.value = null;
  }
};

const fetchCollegeCampuses = async () => {
  try {
    const response = await memberService.collegeCampuses();
    collegeCampuses.value = response?.data || [];
  } catch (error) {
    collegeCampuses.value = [];
    errorMessage.value = error?.message || 'Failed to load college/campus list.';
    await showError(errorMessage.value, 'Load Failed');
  }
};

const resetAddForm = () => {
  addForm.value = {
    full_name: '',
    user_type: 'bor',
    position: '',
    college_campus_id: null,
  };
};

const addUser = async () => {
  if (!addForm.value.full_name) {
    await showError('Full name is required.', 'Validation Error');
    return;
  }
  if (addForm.value.user_type === 'uac' && !addForm.value.college_campus_id) {
    await showError('College/Campus is required for Academic Council users.', 'Validation Error');
    return;
  }

  isAddingUser.value = true;
  try {
    await memberService.create({
      full_name: addForm.value.full_name,
      user_type: addForm.value.user_type,
      position: addForm.value.position,
      college_campus_id: addForm.value.user_type === 'uac' ? addForm.value.college_campus_id : null,
    });
    resetAddForm();
    await fetchUsers();
    activeTab.value = 'users';
    await showSuccess('User added successfully.', 'Create Complete');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to add user.';
    await showError(errorMessage.value, 'Create Failed');
  } finally {
    isAddingUser.value = false;
  }
};

const startEdit = (user) => {
  editUserId.value = user.id;
  editForm.value = {
    full_name: user.full_name || '',
    position: user.position || '',
    college_campus_id: user.college_campus_id ?? null,
  };
};

const cancelEdit = () => {
  editUserId.value = null;
  editForm.value = {
    full_name: '',
    position: '',
    college_campus_id: null,
  };
};

const saveUser = async (user) => {
  if (!editForm.value.full_name) {
    await showError('Full name is required.', 'Validation Error');
    return;
  }

  if (user.user_type === 'uac' && !editForm.value.college_campus_id) {
    await showError('College/Campus is required for Academic Council users.', 'Validation Error');
    return;
  }

  actionLoadingId.value = user.id;
  try {
    await memberService.update(user.id, {
      full_name: editForm.value.full_name,
      position: editForm.value.position,
      college_campus_id: user.user_type === 'uac' ? editForm.value.college_campus_id : null,
    });
    cancelEdit();
    await fetchUsers();
    await showSuccess('User updated successfully.', 'Update Complete');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to update user.';
    await showError(errorMessage.value, 'Update Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const assignDocumentReviewer = async (user) => {
  if (user?.user_type !== 'bor') return;
  if (Number(user.is_document_reviewer ?? 0) === 1) return;
  if (hasDocumentReviewer.value) return;

  const confirmed = await showConfirm({
    title: 'Assign Document Reviewer',
    message: `Set ${user.full_name} as the document reviewer? Only one BOR member can be assigned at a time.`,
    confirmText: 'Assign',
    cancelText: 'Cancel',
  });
  if (!confirmed) return;

  actionLoadingId.value = user.id;
  try {
    await documentReviewService.setReviewer(user.id);
    await fetchUsers();
    await showSuccess('Document reviewer assigned successfully.', 'Assignment Complete');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to assign document reviewer.';
    await showError(errorMessage.value, 'Assignment Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const unsetDocumentReviewer = async (user) => {
  if (user?.user_type !== 'bor') return;
  if (Number(user.is_document_reviewer ?? 0) !== 1) return;

  const confirmed = await showConfirm({
    title: 'Unset Document Reviewer',
    message: `Unset ${user.full_name} as the document reviewer? Recipients will not have a reviewer until you assign one again.`,
    confirmText: 'Unset',
    cancelText: 'Cancel',
  });
  if (!confirmed) return;

  actionLoadingId.value = user.id;
  try {
    await documentReviewService.unsetReviewer();
    await fetchUsers();
    await showSuccess('Document reviewer unset successfully.', 'Unset Complete');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to unset document reviewer.';
    await showError(errorMessage.value, 'Unset Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

const deleteUser = async (user) => {
  const confirmed = await showConfirm({
    title: 'Delete User',
    message: `Delete ${user.full_name}? This will deactivate the user.`,
    confirmText: 'Delete',
    cancelText: 'Cancel',
  });

  if (!confirmed) return;

  actionLoadingId.value = user.id;
  try {
    await memberService.delete(user.id);
    if (editUserId.value === user.id) {
      cancelEdit();
    }
    await fetchUsers();
    await showSuccess('User deleted successfully.', 'Delete Complete');
  } catch (error) {
    errorMessage.value = error?.message || 'Failed to delete user.';
    await showError(errorMessage.value, 'Delete Failed');
  } finally {
    actionLoadingId.value = null;
  }
};

onMounted(async () => {
  await fetchCollegeCampuses();
  await fetchUsers();
  await fetchResetRequests();
});
</script>

<style scoped>
.manager-screen {
  width: 100%;
  min-height: 100%;
  padding: clamp(0.8rem, 1.8vw, 1.3rem);
  background:
    radial-gradient(circle at 20% 0%, rgba(212, 160, 23, 0.2) 0%, transparent 40%),
    linear-gradient(140deg, #1b5e20 0%, #15471a 42%, #0d3715 100%);
  color: #fff8e7;
}

.manager-hero {
  border: 1px solid rgba(212, 160, 23, 0.6);
  border-radius: 0.75rem;
  padding: clamp(0.8rem, 1.5vw, 1.1rem);
  background: rgba(10, 10, 10, 0.24);
}

.hero-kicker {
  font-size: 0.78rem;
  letter-spacing: 0.18em;
  font-weight: 700;
  color: #f1d488;
  text-transform: uppercase;
}

.manager-hero h1 {
  margin-top: 0.35rem;
  font-size: clamp(1.8rem, 4.1vw, 3.2rem);
  line-height: 1.05;
  font-weight: 800;
}

.hero-subtitle {
  margin-top: 0.45rem;
  color: #f6ead0;
  font-size: clamp(0.98rem, 1.5vw, 1.18rem);
}

.manager-card {
  margin-top: 0.9rem;
  border: 1px solid rgba(212, 160, 23, 0.55);
  border-radius: 0.75rem;
  padding: 0.95rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(12, 12, 12, 0.2));
}

.manager-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  flex-wrap: wrap;
}

.tabs {
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.tab-btn {
  border: 1px solid rgba(240, 192, 79, 0.55);
  border-radius: 0.6rem;
  padding: 0.55rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  transition: filter 120ms ease, transform 120ms ease, background 120ms ease;
}

.tab-btn:hover {
  filter: brightness(1.06);
}

.tab-btn-active {
  background: rgba(240, 192, 79, 0.2);
  border-color: rgba(240, 192, 79, 0.9);
}

.refresh-btn {
  border: 1px solid #f0c04f;
  border-radius: 0.5rem;
  padding: 0.45rem 0.9rem;
  color: #fff8e5;
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
  font-weight: 700;
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.manager-card-body {
  margin-top: 0.75rem;
}

.error-text {
  margin-top: 0.65rem;
  color: #ffd2d2;
}

.loading-text {
  margin-top: 0.65rem;
  color: #f6ead0;
}

.table-wrap {
  margin-top: 0.75rem;
  overflow-x: auto;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0.6rem;
  background: rgba(0, 0, 0, 0.18);
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

th,
td {
  border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  padding: 0.6rem 0.6rem;
  text-align: left;
  font-size: 0.95rem;
  vertical-align: top;
}

th {
  color: #f3ce74;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
  white-space: nowrap;
  font-size: 0.82rem;
}

th:nth-child(1) { width: 25%; } /* Name */
th:nth-child(2) { width: 20%; } /* Recipient Group */
th:nth-child(3) { width: 20%; } /* Position */
th:nth-child(4) { width: 20%; } /* College/Campus or Actions */
th:nth-child(5) { width: 15%; } /* Actions when College/Campus is shown */

tbody tr:last-child td {
  border-bottom: 0;
}

.empty-cell {
  padding: 0.8rem 0.5rem;
  color: #f6ead0;
  text-align: center;
}

.doc-cell {
  /* Flexible width controlled by table-layout: fixed */
}

.doc-title {
  font-weight: 800;
  color: #fff8e7;
}

.actions-cell,
.actions-header {
  white-space: nowrap;
  text-align: center;
  vertical-align: middle;
}

.row-no-password {
  background-color: rgba(220, 38, 38, 0.15) !important;
}

.row-no-password:hover {
  background-color: rgba(220, 38, 38, 0.22) !important;
}

.input-no-password {
  border-color: #dc2626 !important;
  background-color: rgba(254, 226, 226, 0.6) !important;
  color: #7f1d1d !important;
}

.input-no-password:focus {
  border-color: #991b1b !important;
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.4) !important;
}

select option {
  background-color: #ffffff;
  color: #000000;
}

/* Add User Form Styles */
.add-user-section {
  max-width: 900px;
  margin: 0 auto;
}

.upload-form {
  display: grid;
  gap: 0.85rem;
}

.form-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: 1fr;
}

@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.field {
  min-width: 0;
}

.field-label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #f3ce74;
}

.field-help {
  margin-top: 0.35rem;
  color: #f6ead0;
  font-size: 0.86rem;
}

.field-help-emerald {
  color: #d7f3dd;
}

.field-input {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 0.6rem;
  padding: 0.75rem 0.9rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  outline: none;
  font-size: 1rem;
  transition: border-color 120ms ease, box-shadow 120ms ease, background 120ms ease;
}

.field-input::placeholder {
  color: rgba(246, 234, 208, 0.72);
}

.field-input:focus {
  border-color: rgba(240, 192, 79, 0.95);
  box-shadow: 0 0 0 3px rgba(240, 192, 79, 0.2);
  background: rgba(0, 0, 0, 0.25);
}

.actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  padding-top: 0.25rem;
  margin-top: 0.5rem;
}

.btn {
  border: 1px solid #f0c04f;
  border-radius: 0.6rem;
  min-width: 170px;
  padding: 0.75rem 1.2rem;
  font-size: 1rem;
  font-weight: 700;
  color: #fff8e5;
  transition: filter 120ms ease, transform 120ms ease, opacity 120ms ease;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(to bottom right, #6f1717, #4a0f0f);
}

.btn-primary:hover:not(:disabled) {
  filter: brightness(1.07);
  transform: translateY(-1px);
}

.btn-secondary {
  background: rgba(0, 0, 0, 0.22);
}

.btn-secondary:hover:not(:disabled) {
  filter: brightness(1.06);
  transform: translateY(-1px);
}

.filter-section {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  padding: 0.75rem 0;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .filter-section {
    grid-template-columns: 1fr;
  }
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-label {
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #f3ce74;
  font-weight: 700;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  width: 18px;
  height: 18px;
  color: rgba(212, 160, 23, 0.6);
  pointer-events: none;
}

.search-input {
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 0.6rem;
  padding: 0.65rem 0.9rem 0.65rem 2.5rem;
  background: rgba(0, 0, 0, 0.18);
  color: #fff8e7;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 120ms ease, box-shadow 120ms ease;
}

.search-input::placeholder {
  color: rgba(246, 234, 208, 0.6);
}

.search-input:focus {
  border-color: rgba(240, 192, 79, 0.95);
  box-shadow: 0 0 0 3px rgba(240, 192, 79, 0.2);
}
</style>

