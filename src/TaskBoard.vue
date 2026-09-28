<script setup lang="ts">
import { ref, computed } from 'vue'

// Mock data for demonstration - tasks with description, importance (0-100), and due date
const mockTasks = ref<Task[]>([
  { id: 1, description: 'Finish project proposal', importance: 90, dueDate: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000) }, // Due in 2 days
  { id: 2, description: 'Review code changes', importance: 80, dueDate: new Date(Date.now() + 1 * 24 * 60 * 60 * 1000) }, // Due in 1 day
  { id: 3, description: 'Team meeting', importance: 70, dueDate: new Date(Date.now() + 0.5 * 24 * 60 * 60 * 1000) }, // Due in 12 hours
  { id: 4, description: 'Plan next sprint', importance: 85, dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) }, // Due in 1 week
  { id: 5, description: 'Document API', importance: 60, dueDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000) }, // Due in 2 weeks
  { id: 6, description: 'Clean up old files', importance: 30, dueDate: new Date(Date.now() + 1 * 24 * 60 * 60 * 1000) }, // Due in 1 day, low importance
  { id: 7, description: 'Research new tech', importance: 75, dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) }, // Due in 1 month
  { id: 8, description: 'Write blog post', importance: 50, dueDate: new Date(Date.now() + 60 * 24 * 60 * 60 * 1000) }, // Due in 2 months
  { id: 9, description: 'Fix critical bug', importance: 95, dueDate: new Date(Date.now() + 0.25 * 24 * 60 * 60 * 1000) }, // Due in 6 hours - URGENT
  { id: 10, description: 'Archive old emails', importance: 20, dueDate: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000) }, // Due in 3 days, low importance
])

interface Task {
  id: number
  description: string
  importance: number
  dueDate: Date
}

// Board dimensions
const BOARD_WIDTH = 1200
const BOARD_HEIGHT = 800
const PADDING = 40

/**
 * Calculate X position using logarithmic scale for due date
 * x = log(daysUntilDue + 1) mapped to board width
 */
function calculateX(dueDate: Date): number {
  const now = new Date()
  const msUntilDue = dueDate.getTime() - now.getTime()
  const daysUntilDue = msUntilDue / (1000 * 60 * 60 * 24)
  
  // Logarithmic scale: log(days + 1) to compress time
  // Map to board width with padding
  const logScale = Math.log(daysUntilDue + 1)
  
  // Normalize: tasks due very soon (< 1 day) should be near left edge
  // Tasks due in ~7 days should be around middle
  // Tasks due in 30+ days should be towards right
  // Using a scaling factor to spread out the range
  const x = (logScale * 150) + PADDING
  
  // Clamp to board boundaries
  return Math.max(PADDING, Math.min(BOARD_WIDTH - PADDING, x))
}

/**
 * Calculate Y position using linear scale for importance
 * y = importance mapped to board height (100 at top, 0 at bottom)
 */
function calculateY(importance: number): number {
  // Importance 0-100, with 100 at the top
  const y = BOARD_HEIGHT - PADDING - (importance / 100) * (BOARD_HEIGHT - 2 * PADDING)
  return y
}

// Calculated positions for all tasks
const taskPositions = computed(() => {
  return mockTasks.value.map(task => ({
    ...task,
    x: calculateX(task.dueDate),
    y: calculateY(task.importance)
  }))
})

// Add a new random task for testing
function addRandomTask() {
  const importance = Math.floor(Math.random() * 101)
  const daysInFuture = Math.random() * 60 + 0.1 // 0.1 to 60 days
  const dueDate = new Date(Date.now() + daysInFuture * 24 * 60 * 60 * 1000)
  
  mockTasks.value.push({
    id: mockTasks.value.length + 1,
    description: `Task ${mockTasks.value.length + 1}`,
    importance,
    dueDate
  })
}

// Reset to original mock data
function resetTasks() {
  mockTasks.value = [
    { id: 1, description: 'Finish project proposal', importance: 90, dueDate: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000) },
    { id: 2, description: 'Review code changes', importance: 80, dueDate: new Date(Date.now() + 1 * 24 * 60 * 60 * 1000) },
    { id: 3, description: 'Team meeting', importance: 70, dueDate: new Date(Date.now() + 0.5 * 24 * 60 * 60 * 1000) },
    { id: 4, description: 'Plan next sprint', importance: 85, dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) },
    { id: 5, description: 'Document API', importance: 60, dueDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000) },
    { id: 6, description: 'Clean up old files', importance: 30, dueDate: new Date(Date.now() + 1 * 24 * 60 * 60 * 1000) },
    { id: 7, description: 'Research new tech', importance: 75, dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) },
    { id: 8, description: 'Write blog post', importance: 50, dueDate: new Date(Date.now() + 60 * 24 * 60 * 60 * 1000) },
    { id: 9, description: 'Fix critical bug', importance: 95, dueDate: new Date(Date.now() + 0.25 * 24 * 60 * 60 * 1000) },
    { id: 10, description: 'Archive old emails', importance: 20, dueDate: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000) },
  ]
}

// Format due date for display
function formatDueDate(date: Date): string {
  const now = new Date()
  const msUntilDue = date.getTime() - now.getTime()
  const days = Math.floor(msUntilDue / (1000 * 60 * 60 * 24))
  const hours = Math.floor((msUntilDue % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (days === 0) {
    return `Due in ${hours}h`
  } else if (days === 1) {
    return `Due in 1 day`
  } else {
    return `Due in ${days}d`
  }
}
</script>

<template>
  <div class="task-board-container">
    <h2>Eisenhower Board (Draft)</h2>
    
    <div class="controls">
      <button @click="addRandomTask" class="btn">Add Random Task</button>
      <button @click="resetTasks" class="btn btn-secondary">Reset</button>
      <span class="task-count">{{ mockTasks.length }} tasks</span>
    </div>

    <div class="board-info">
      <p>
        <strong>Positioning Logic:</strong> X = log(daysUntilDue + 1) · 150, Y = importance (0-100)
      </p>
      <p>
        <strong>Eisenhower Matrix Quadrants:</strong>
      </p>
    </div>

    <!-- The Board -->
    <div class="task-board" :style="{ width: `${BOARD_WIDTH}px`, height: `${BOARD_HEIGHT}px` }">
      
      <!-- Quadrant lines and labels -->
      <div class="quadrant-line horizontal" :style="{ top: `${BOARD_HEIGHT / 2}px` }"></div>
      <div class="quadrant-line vertical" :style="{ left: `${BOARD_WIDTH / 2}px` }"></div>
      
      <!-- Quadrant labels -->
      <div class="quadrant-label top-left">Urgent & Important</div>
      <div class="quadrant-label top-right">Important, Not Urgent</div>
      <div class="quadrant-label bottom-left">Urgent, Not Important</div>
      <div class="quadrant-label bottom-right">Neither</div>

      <!-- Task items -->
      <div
        v-for="task in taskPositions"
        :key="task.id"
        class="task-item"
        :style="{
          left: `${task.x}px`,
          top: `${task.y}px`,
          transform: 'translate(-50%, -50%)'
        }"
        :class="{
          'high-importance': task.importance >= 80,
          'medium-importance': task.importance >= 50 && task.importance < 80,
          'low-importance': task.importance < 50,
          'very-urgent': (task.dueDate.getTime() - new Date().getTime()) < 24 * 60 * 60 * 1000,
          'urgent': (task.dueDate.getTime() - new Date().getTime()) < 3 * 24 * 60 * 60 * 1000
        }"
      >
        <div class="task-header">
          <span class="task-id">#{{ task.id }}</span>
          <span class="task-importance">{{ task.importance }}/100</span>
        </div>
        <div class="task-description">{{ task.description }}</div>
        <div class="task-due">{{ formatDueDate(task.dueDate) }}</div>
      </div>
    </div>

    <div class="legend">
      <h4>Legend:</h4>
      <div class="legend-item">
        <span class="legend-color high-importance"></span>
        <span>High Importance (80-100)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color medium-importance"></span>
        <span>Medium Importance (50-79)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color low-importance"></span>
        <span>Low Importance (0-49)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color very-urgent"></span>
        <span>Very Urgent (&lt; 24h)</span>
      </div>
      <div class="legend-item">
        <span class="legend-color urgent"></span>
        <span>Urgent (&lt; 3 days)</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-board-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.task-board-container h2 {
  text-align: center;
  color: #333;
  margin-bottom: 20px;
}

.controls {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  align-items: center;
}

.btn {
  padding: 10px 20px;
  background-color: #0082c9;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #006aa3;
}

.btn-secondary {
  background-color: #6c757d;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.task-count {
  color: #666;
  font-size: 14px;
}

.board-info {
  background-color: #f8f9fa;
  padding: 10px 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 13px;
  color: #555;
}

.board-info p {
  margin: 5px 0;
}

/* The Board */
.task-board {
  position: relative;
  border: 2px solid #ddd;
  border-radius: 8px;
  background: linear-gradient(to bottom, #f0f8ff, #ffffff);
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.quadrant-line {
  position: absolute;
  background-color: rgba(0, 0, 0, 0.1);
  z-index: 1;
}

.quadrant-line.horizontal {
  width: 100%;
  height: 2px;
  left: 0;
}

.quadrant-line.vertical {
  width: 2px;
  height: 100%;
  top: 0;
}

.quadrant-label {
  position: absolute;
  font-size: 11px;
  font-weight: bold;
  color: #666;
  opacity: 0.7;
  z-index: 1;
}

.quadrant-label.top-left {
  top: 10px;
  left: 10px;
}

.quadrant-label.top-right {
  top: 10px;
  right: 10px;
  text-align: right;
}

.quadrant-label.bottom-left {
  bottom: 10px;
  left: 10px;
}

.quadrant-label.bottom-right {
  bottom: 10px;
  right: 10px;
  text-align: right;
}

/* Task Items */
.task-item {
  position: absolute;
  width: 200px;
  background: white;
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  z-index: 10;
  transition: transform 0.2s, box-shadow 0.2s;
  font-size: 13px;
}

.task-item:hover {
  transform: translate(-50%, -50%) scale(1.05);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  z-index: 20;
}

.task-header {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #666;
  margin-bottom: 5px;
  border-bottom: 1px solid #eee;
  padding-bottom: 3px;
}

.task-description {
  font-weight: 500;
  color: #333;
  margin-bottom: 5px;
  word-break: break-word;
}

.task-due {
  font-size: 11px;
  color: #888;
  text-align: right;
}

/* Importance colors */
.high-importance {
  border-left: 4px solid #e74c3c;
}

.medium-importance {
  border-left: 4px solid #f39c12;
}

.low-importance {
  border-left: 4px solid #95a5a6;
}

/* Urgency colors */
.very-urgent {
  background-color: #fff5f5;
}

.urgent {
  background-color: #fffaf0;
}

/* Legend */
.legend {
  margin-top: 20px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.legend h4 {
  margin-top: 0;
  margin-bottom: 10px;
  color: #333;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 5px;
}

.legend-color {
  display: inline-block;
  width: 20px;
  height: 14px;
  border-radius: 2px;
}

.legend-color.high-importance {
  background-color: #e74c3c;
}

.legend-color.medium-importance {
  background-color: #f39c12;
}

.legend-color.low-importance {
  background-color: #95a5a6;
}

.legend-color.very-urgent {
  background-color: #ffe0e0;
  border: 1px solid #e74c3c;
}

.legend-color.urgent {
  background-color: #fffae0;
  border: 1px solid #f39c12;
}
</style>
