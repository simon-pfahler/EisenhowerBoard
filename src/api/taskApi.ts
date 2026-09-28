import axios from 'axios'

// Define types for our API responses
interface Task {
	id: number | null
	user_id: string
	description: string
	importance: number
	due_date: string
	created_at: string
	updated_at: string
}

const appId = 'eisenhowerboard'

/**
 * Build OCS URL for our app's API endpoints
 * Uses relative path to work within Nextcloud's proxy
 */
function buildApiUrl(endpoint: string): string {
	return `/ocs/v2.php/apps/${appId}/${endpoint}`
}

/**
 * Make an OCS API request with proper headers and error handling
 */
async function ocsRequest<T>(
	method: 'get' | 'post' | 'put' | 'delete',
	endpoint: string,
	data?: any
): Promise<T> {
	const url = buildApiUrl(endpoint)
	
	const headers: Record<string, string> = {
		'Content-Type': 'application/json',
		'OCS-APIRequest': 'true',
	}
	
	const config = {
		method,
		url,
		headers,
		withCredentials: true,
		data,
	}
	
	try {
		const response = await axios(config)
		
		// Check for OCS error response
		if (response.data?.ocs?.meta?.status !== 'ok') {
			throw new Error(
				response.data?.ocs?.meta?.message || 
				`API error: ${response.data?.ocs?.meta?.statuscode || 'unknown'}`
			)
		}
		
		return response.data?.ocs?.data as T
	} catch (error) {
		console.error('OCS API Error:', error)
		if (axios.isAxiosError(error)) {
			const msg = error.response?.data?.ocs?.meta?.message || 
				error.response?.statusText || 
				error.message
			throw new Error(`API request failed: ${msg}`)
		}
		throw error
	}
}

/**
 * Get all tasks for the current user
 */
export async function getTasks(): Promise<Task[]> {
	return ocsRequest<Task[]>('get', 'api/tasks')
}

/**
 * Create a new task
 */
export async function createTask(
	description: string,
	importance: number,
	dueDate: string
): Promise<Task> {
	return ocsRequest<Task>('post', 'api/tasks', {
		description,
		importance,
		dueDate,
	})
}

/**
 * Get a specific task by ID
 */
export async function getTask(id: number): Promise<Task> {
	return ocsRequest<Task>('get', `api/tasks/${id}`)
}

/**
 * Update an existing task
 */
export async function updateTask(
	id: number,
	data: {
		description?: string
		importance?: number
		dueDate?: string
	}
): Promise<Task> {
	return ocsRequest<Task>('put', `api/tasks/${id}`, data)
}

/**
 * Delete a task
 */
export async function deleteTask(id: number): Promise<void> {
	return ocsRequest<void>('delete', `api/tasks/${id}`)
}

/**
 * Convert API task to frontend task format
 */
export function apiTaskToFrontendTask(apiTask: Task): any {
	return {
		id: apiTask.id,
		name: apiTask.description, // Map description to name for consistency
		description: apiTask.description,
		importance: apiTask.importance,
		dueDate: new Date(apiTask.due_date),
		createdAt: new Date(apiTask.created_at),
		updatedAt: new Date(apiTask.updated_at),
	}
}

export default {
	getTasks,
	createTask,
	getTask,
	updateTask,
	deleteTask,
	apiTaskToFrontendTask,
}
