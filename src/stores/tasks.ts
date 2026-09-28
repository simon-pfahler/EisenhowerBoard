import { ref, computed } from 'vue'
import { getTasks, createTask, updateTask, deleteTask, apiTaskToFrontendTask } from '../api/taskApi'

// Define the Task type for frontend use
interface FrontendTask {
	id: number | null
	name: string
	description: string
	importance: number
	dueDate: Date
	createdAt: Date
	updatedAt: Date
}

// Store for managing tasks
export const useTaskStore = () => {
	const tasks = ref<FrontendTask[]>([])
	const loading = ref<boolean>(false)
	const error = ref<string | null>(null)
	const showTaskForm = ref<boolean>(false)
	const taskToEdit = ref<FrontendTask | null>(null)

	/**
	 * Fetch all tasks from the API
	 */
	async function fetchTasks(): Promise<void> {
		loading.value = true
		error.value = null

		try {
			const apiTasks = await getTasks()
			tasks.value = apiTasks.map(apiTaskToFrontendTask)
		} catch (err) {
			console.error('Failed to fetch tasks:', err)
			error.value = err instanceof Error ? err.message : 'Failed to load tasks. Please try again.'
		} finally {
			loading.value = false
		}
	}

	/**
	 * Open form to add new task
	 */
	function openAddForm(): void {
		taskToEdit.value = null
		showTaskForm.value = true
	}

	/**
	 * Open form to edit existing task
	 */
	function openEditForm(task: FrontendTask): void {
		taskToEdit.value = task
		showTaskForm.value = true
	}

	/**
	 * Close the task form
	 */
	function closeForm(): void {
		showTaskForm.value = false
		taskToEdit.value = null
	}

	/**
	 * Create a new task
	 * Maps name to description for API compatibility
	 */
	async function addTask(
		name: string,
		description: string,
		importance: number,
		dueDate: string
	): Promise<FrontendTask | null> {
		loading.value = true
		error.value = null

		try {
			// Map name to description for API (backend only has description field)
			// If name is provided and description is empty, use name as description
			const finalDescription = description || name
			
			const apiTask = await createTask(finalDescription, importance, dueDate)
			const newTask = apiTaskToFrontendTask(apiTask) as FrontendTask
			
			// Set name from the provided name parameter
			if (name) {
				newTask.name = name
			}
			tasks.value = [...tasks.value, newTask]
			return newTask
		} catch (err) {
			console.error('Failed to create task:', err)
			error.value = err instanceof Error ? err.message : 'Failed to create task. Please try again.'
			return null
		} finally {
			loading.value = false
		}
	}

	/**
	 * Update an existing task
	 * Maps name to description for API compatibility
	 */
	async function editTask(
		id: number,
		data: {
			name?: string
			description?: string
			importance?: number
			dueDate?: string
		}
	): Promise<FrontendTask | null> {
		loading.value = true
		error.value = null

		try {
			// Map name to description for API (backend only has description field)
			const updateData: any = {}
			
			// If name is provided, update description with name (or combine with description)
			if (data.name !== undefined) {
				updateData.description = data.name
			}
			
			if (data.description !== undefined) {
				// If both name and description provided, description takes precedence
				updateData.description = data.description
			}
			
			if (data.importance !== undefined) {
				updateData.importance = data.importance
			}
			
			if (data.dueDate !== undefined) {
				updateData.dueDate = data.dueDate
			}

			const apiTask = await updateTask(id, updateData)
			const updatedTask = apiTaskToFrontendTask(apiTask) as FrontendTask

			// Update the task in the store
			tasks.value = tasks.value.map(task =>
				task.id === id ? updatedTask : task
			)

			return updatedTask
		} catch (err) {
			console.error('Failed to update task:', err)
			error.value = err instanceof Error ? err.message : 'Failed to update task. Please try again.'
			return null
		} finally {
			loading.value = false
		}
	}

	/**
	 * Delete a task
	 */
	async function removeTask(id: number | null): Promise<boolean> {
		if (id === null) return false
		
		loading.value = true
		error.value = null

		try {
			await deleteTask(id)
			tasks.value = tasks.value.filter(task => task.id !== id)
			return true
		} catch (err) {
			console.error('Failed to delete task:', err)
			error.value = err instanceof Error ? err.message : 'Failed to delete task. Please try again.'
			return false
		} finally {
			loading.value = false
		}
	}

	/**
	 * Get tasks by quadrant for Eisenhower Matrix
	 */
	const quadrants = computed(() => {
		const now = new Date()
		
		return {
			'urgent-important': tasks.value.filter(task => {
				const daysUntilDue = (task.dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
				return daysUntilDue <= 3 && task.importance >= 80
			}),
			'important-not-urgent': tasks.value.filter(task => {
				const daysUntilDue = (task.dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
				return daysUntilDue > 3 && task.importance >= 80
			}),
			'urgent-not-important': tasks.value.filter(task => {
				const daysUntilDue = (task.dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
				return daysUntilDue <= 3 && task.importance < 80
			}),
			'not-urgent-not-important': tasks.value.filter(task => {
				const daysUntilDue = (task.dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
				return daysUntilDue > 3 && task.importance < 80
			}),
		}
	})

	/**
	 * Calculate position for a task
	 */
	function calculatePosition(task: FrontendTask): { x: number; y: number } {
		const now = new Date()
		const msUntilDue = task.dueDate.getTime() - now.getTime()
		const daysUntilDue = msUntilDue / (1000 * 60 * 60 * 24)
		
		// Logarithmic scale for x-axis
		const logScale = Math.log(Math.abs(daysUntilDue) + 1)
		const x = (daysUntilDue < 0 ? -1 : 1) * logScale * 150 + 40
		
		// Linear scale for y-axis
		const y = 40 + ((100 - task.importance) / 100) * (800 - 80)
		
		return { x, y }
	}

	/**
	 * Get all tasks with their calculated positions
	 */
	const positionedTasks = computed(() => {
		return tasks.value.map(task => ({
			...task,
			...calculatePosition(task),
		}))
	})

	return {
		tasks,
		positionedTasks,
		quadrants,
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
		calculatePosition,
	}
}

// Create a singleton instance for easy access
export const taskStore = useTaskStore()
