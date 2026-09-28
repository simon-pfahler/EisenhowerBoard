<script setup lang="ts">
import { onMounted } from 'vue'
import TaskForm from './components/TaskForm.vue'
import { useTaskStore } from './stores/tasks'

const {
	tasks,
	positionedTasks,
	loading,
	error,
	showTaskForm,
	taskToEdit,
	fetchTasks,
	addTask,
	editTask,
	removeTask,
	openAddForm,
	openEditForm,
	closeForm,
} = useTaskStore()

// Board dimensions
const BOARD_WIDTH = 1200
const BOARD_HEIGHT = 800
const PADDING = 40

// Fetch tasks on component mount
onMounted(() => {
	fetchTasks()
})

// Handle form submission
async function handleSubmit(formData: any) {
	if (taskToEdit.value) {
		// Edit existing task
		await editTask(taskToEdit.value.id!, {
			name: formData.name,
			description: formData.description,
			importance: formData.importance,
			dueDate: formData.dueDate,
		})
	} else {
		// Add new task - map name to description for API
		await addTask(
			formData.name,
			formData.description,
			formData.importance,
			formData.dueDate
		)
	}
	closeForm()
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
	} else if (days < 0) {
		return `Overdue by ${Math.abs(days)}d`
	} else {
		return `Due in ${days}d`
	}
}
</script>

<template>
	<div class="task-board-container">
		<h2>Eisenhower Board</h2>
		
		<div class="controls">
			<button @click="openAddForm" class="btn" :disabled="loading">
				<span v-if="loading">Loading...</span>
				<span v-else>Add Task</span>
			</button>
			<button @click="fetchTasks" class="btn btn-secondary" :disabled="loading">
				Refresh
			</button>
			<span class="task-count">{{ tasks.length }} tasks</span>
		</div>

		<div v-if="error" class="error-message">
			{{ error }}
			<button @click="error = null" class="btn-close">×</button>
		</div>

		<div class="board-info">
			<p>
				<strong>Positioning:</strong> X = log(daysUntilDue + 1) · 150, Y = importance (0-100)
			</p>
		</div>

		<!-- Task Form Modal -->
		<TaskForm
			v-model="showTaskForm"
			:taskToEdit="taskToEdit"
			@submit="handleSubmit"
			@cancel="closeForm"
		/>

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
				v-for="task in positionedTasks"
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
				@click="openEditForm(task)"
			>
				<div class="task-header">
					<span class="task-id">#{{ task.id }}</span>
					<span class="task-importance">{{ task.importance }}/100</span>
					<button @click.stop="removeTask(task.id)" class="btn-delete" title="Delete">×</button>
				</div>
				<div class="task-name">{{ task.name || task.description }}</div>
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

.btn:hover:not(:disabled) {
	background-color: #006aa3;
}

.btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

.btn-secondary {
	background-color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
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

.error-message {
	background-color: #f8d7da;
	color: #721c24;
	padding: 10px 15px;
	border-radius: 4px;
	margin-bottom: 20px;
	font-size: 14px;
	display: flex;
	align-items: center;
	gap: 10px;
}

.btn-close {
	background: none;
	border: none;
	color: #721c24;
	cursor: pointer;
	font-size: 18px;
	padding: 0 0 0 5px;
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
	align-items: center;
	font-size: 11px;
	color: #666;
	margin-bottom: 5px;
	border-bottom: 1px solid #eee;
	padding-bottom: 3px;
}

.task-name {
	font-weight: 600;
	color: #333;
	margin-bottom: 3px;
	word-break: break-word;
}

.task-description {
	font-size: 12px;
	color: #666;
	margin-bottom: 5px;
	word-break: break-word;
	max-height: 60px;
	overflow: hidden;
}

.task-due {
	font-size: 11px;
	color: #888;
	text-align: right;
}

.btn-delete {
	background: none;
	border: none;
	color: #dc3545;
	cursor: pointer;
	font-size: 16px;
	padding: 0;
	margin-left: 5px;
}

.btn-delete:hover {
	color: #c82333;
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
