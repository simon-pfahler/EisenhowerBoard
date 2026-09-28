<script setup lang="ts">
import { ref, watch, Teleport } from 'vue'

interface TaskFormData {
	name: string
	description: string
	importance: number
	dueDate: string
}

const props = defineProps({
	modelValue: {
		type: Boolean,
		required: true,
	},
	taskToEdit: {
		type: Object as () => any | null,
		default: null,
	},
})

const emit = defineEmits(['submit', 'cancel', 'update:modelValue'])

const formData = ref<TaskFormData>({
	name: '',
	description: '',
	importance: 50,
	dueDate: formatDateForInput(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)), // Default: 7 days from now
})

const formErrors = ref<Record<string, string>>({})

// Watch for taskToEdit changes to populate the form
watch(() => props.taskToEdit, (newTask) => {
	if (newTask) {
		formData.value = {
			name: newTask.name || newTask.description || '',
			description: newTask.description || '',
			importance: newTask.importance || 50,
			dueDate: newTask.dueDate ? formatDateForInput(new Date(newTask.dueDate)) : formatDateForInput(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)),
		}
	} else {
		// Reset form for new task
		formData.value = {
			name: '',
			description: '',
			importance: 50,
			dueDate: formatDateForInput(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)),
		}
	}
}, { immediate: true })

function formatDateForInput(date: Date): string {
	// Format as YYYY-MM-DD for date input
	return date.toISOString().split('T')[0]
}

function validateForm(): boolean {
	const errors: Record<string, string> = {}
	
	if (!formData.value.name.trim()) {
		errors.name = 'Name is required'
	}
	
	if (!formData.value.description.trim()) {
		errors.description = 'Description is required'
	}
	
	if (formData.value.importance < 0 || formData.value.importance > 100) {
		errors.importance = 'Importance must be between 0 and 100'
	}
	
	if (!formData.value.dueDate) {
		errors.dueDate = 'Due date is required'
	}
	
	formErrors.value = errors
	return Object.keys(errors).length === 0
}

function handleSubmit() {
	if (!validateForm()) {
		return
	}
	
	emit('submit', {
		...formData.value,
	})
	
	// Close the form
	emit('update:modelValue', false)
	
	// Reset form after submit
	formData.value = {
		name: '',
		description: '',
		importance: 50,
		dueDate: formatDateForInput(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)),
	}
}

function handleCancel() {
	emit('cancel')
	emit('update:modelValue', false)
}

function handleKeydown(e: KeyboardEvent) {
	if (e.key === 'Escape') {
		handleCancel()
	}
	if (e.key === 'Enter' && e.target instanceof HTMLInputElement) {
		// Don't submit on Enter in inputs (user can use the button)
		e.preventDefault()
	}
}
</script>

<template>
	<!-- Modal overlay -->
	<Teleport to="body">
		<div
			v-if="modelValue"
			class="modal-overlay"
			@click="handleCancel"
			@keydown="handleKeydown"
		>
			<!-- Modal content -->
			<div class="modal-content" @click.stop>
				<h3>{{ taskToEdit ? 'Edit Task' : 'Add New Task' }}</h3>
				
				<form @submit.prevent="handleSubmit">
					<!-- Name field -->
					<div class="form-group">
						<label for="task-name">Name *</label>
						<input
							id="task-name"
							v-model="formData.name"
							type="text"
							placeholder="Task name"
							:class="{ 'has-error': formErrors.name }"
							autofocus
						/>
						<span v-if="formErrors.name" class="error-message">{{ formErrors.name }}</span>
					</div>

					<!-- Description field -->
					<div class="form-group">
						<label for="task-description">Description *</label>
						<textarea
							id="task-description"
							v-model="formData.description"
							placeholder="Detailed description..."
							rows="3"
							:class="{ 'has-error': formErrors.description }"
						></textarea>
						<span v-if="formErrors.description" class="error-message">{{ formErrors.description }}</span>
					</div>

					<!-- Importance field -->
					<div class="form-group">
						<label for="task-importance">Importance (0-100)</label>
						<div class="slider-container">
							<input
								id="task-importance"
								v-model.number="formData.importance"
								type="range"
								min="0"
								max="100"
								:class="{ 'has-error': formErrors.importance }"
							/>
							<span class="slider-value">{{ formData.importance }}</span>
						</div>
						<span v-if="formErrors.importance" class="error-message">{{ formErrors.importance }}</span>
					</div>

					<!-- Due Date field -->
					<div class="form-group">
						<label for="task-due-date">Due Date *</label>
						<input
							id="task-due-date"
							v-model="formData.dueDate"
							type="date"
							:class="{ 'has-error': formErrors.dueDate }"
						/>
						<span v-if="formErrors.dueDate" class="error-message">{{ formErrors.dueDate }}</span>
					</div>

					<!-- Buttons -->
					<div class="form-actions">
						<button type="button" @click="handleCancel" class="btn btn-secondary">
							Cancel
						</button>
						<button type="submit" class="btn btn-primary">
							{{ taskToEdit ? 'Save Changes' : 'Add Task' }}
						</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<style scoped>
.modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: rgba(0, 0, 0, 0.5);
	z-index: 1000;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.modal-content {
	background: white;
	border-radius: 8px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
	width: 100%;
	max-width: 500px;
	max-height: 90vh;
	overflow-y: auto;
	padding: 24px;
}

.modal-content h3 {
	margin: 0 0 20px 0;
	color: #333;
	font-size: 20px;
}

.form-group {
	margin-bottom: 16px;
}

.form-group label {
	display: block;
	margin-bottom: 6px;
	font-weight: 500;
	color: #444;
	font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea {
	width: 100%;
	padding: 10px;
	border: 1px solid #ced4da;
	border-radius: 4px;
	font-size: 14px;
	box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group input.has-error:focus,
.form-group textarea.has-error:focus {
	outline: none;
	border-color: #0082c9;
	box-shadow: 0 0 0 2px rgba(0, 130, 201, 0.25);
}

.form-group input.has-error,
.form-group textarea.has-error {
	border-color: #dc3545;
}

.form-group textarea {
	resize: vertical;
	min-height: 80px;
}

.error-message {
	color: #dc3545;
	font-size: 12px;
	margin-top: 4px;
	display: block;
}

.slider-container {
	display: flex;
	align-items: center;
	gap: 12px;
}

.slider-container input[type="range"] {
	flex: 1;
	padding: 0;
	border: none;
	background: transparent;
	cursor: pointer;
}

.slider-container input[type="range"]:focus {
	outline: none;
}

.slider-value {
	min-width: 40px;
	text-align: center;
	font-weight: bold;
	color: #0082c9;
}

.form-actions {
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	margin-top: 24px;
	padding-top: 16px;
	border-top: 1px solid #eee;
}

.btn {
	padding: 10px 20px;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	font-size: 14px;
	font-weight: 500;
	transition: background-color 0.2s, opacity 0.2s;
}

.btn-primary {
	background-color: #0082c9;
	color: white;
}

.btn-primary:hover {
	background-color: #006aa3;
}

.btn-secondary {
	background-color: #6c757d;
	color: white;
}

.btn-secondary:hover {
	background-color: #5a6268;
}

.btn:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
</style>
