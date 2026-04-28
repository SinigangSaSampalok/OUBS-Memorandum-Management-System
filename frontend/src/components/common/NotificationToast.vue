<template>
  <transition name="toast-fade">
    <div
      v-if="visible"
      class="fixed bottom-5 right-5 max-w-md bg-white rounded-lg shadow-2xl border-l-4 overflow-hidden hover:shadow-3xl transition-all z-40 cursor-pointer"
      :class="`border-l-${getTypeColor()}`"
      @click="handleClick"
      @mouseenter="pauseAutoClose"
      @mouseleave="resumeAutoClose"
    >
      <div class="p-4 flex gap-3">
        <!-- Icon -->
        <div class="flex-shrink-0 flex items-start pt-1">
          <component :is="getIcon()" class="w-5 h-5" :class="`text-${getTypeColor()}`" />
        </div>

        <!-- Content -->
        <div class="flex-grow">
          <h3 class="font-semibold text-sm" :class="`text-${getTypeColor()}`">
            {{ title }}
          </h3>
          <p class="text-xs text-gray-600 mt-1">{{ message }}</p>
        </div>

        <!-- Close button -->
        <button
          class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
          @click.stop="close"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Progress bar -->
      <div class="h-1 bg-gray-200 overflow-hidden">
        <div
          class="h-full transition-all"
          :class="`bg-${getTypeColor()}`"
          :style="{ width: `${progressWidth}%` }"
        ></div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  notification: {
    type: Object,
    required: true,
  },
  duration: {
    type: Number,
    default: 3000,
  },
  onClose: {
    type: Function,
    default: () => {},
  },
  onClick: {
    type: Function,
    default: () => {},
  },
})

const visible = ref(true)
const progressWidth = ref(100)
let closeTimer = null
let progressTimer = null
let isPaused = false

const getTypeColor = () => {
  const types = {
    welcome: 'blue',
    document_new: 'green',
    reset_request: 'yellow',
    document_closed: 'red',
    document_review: 'purple',
    document_deadline: 'orange',
    document_completed: 'emerald',
    reply_success: 'teal',
    error: 'red',
    success: 'green',
    info: 'blue',
  }
  return types[props.notification.type] || 'blue'
}

const getIcon = () => {
  const icons = {
    welcome: 'IconWelcome',
    document_new: 'IconDocument',
    reset_request: 'IconKey',
    document_closed: 'IconClosed',
    document_review: 'IconReview',
    document_deadline: 'IconClock',
    document_completed: 'IconCheck',
    reply_success: 'IconSuccess',
    error: 'IconError',
    success: 'IconSuccess',
    info: 'IconInfo',
  }
  return icons[props.notification.type] || 'IconInfo'
}

const handleClick = () => {
  props.onClick(props.notification)
  close()
}

const close = () => {
  visible.value = false
  clearTimers()
  props.onClose()
}

const pauseAutoClose = () => {
  isPaused = true
  clearTimers()
}

const resumeAutoClose = () => {
  if (isPaused) {
    isPaused = false
    startAutoClose()
  }
}

const startAutoClose = () => {
  if (isPaused) return

  const remainingTime = (progressWidth.value / 100) * props.duration
  const progressStep = 100 / (props.duration / 50)

  progressTimer = setInterval(() => {
    if (!isPaused) {
      progressWidth.value = Math.max(0, progressWidth.value - progressStep)
    }
  }, 50)

  closeTimer = setTimeout(() => {
    if (!isPaused) {
      close()
    }
  }, remainingTime)
}

const clearTimers = () => {
  if (closeTimer) clearTimeout(closeTimer)
  if (progressTimer) clearInterval(progressTimer)
}

onMounted(() => {
  startAutoClose()
})

onUnmounted(() => {
  clearTimers()
})
</script>

<style scoped>
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.3s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateX(400px);
}
</style>
