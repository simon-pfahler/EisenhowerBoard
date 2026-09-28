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
 * Build URL for our app's API endpoints
 * Uses relative path to work within Nextcloud's proxy
 */
function buildApiUrl(endpoint: string): string {
	return `/apps/${appId}/${endpoint}`
}

/**
 * Make an API request with proper headers and error handling
 */
async function ocsRequest<T>(
	method: 'get' | 'post' | 'put' | 'delete',
	endpoint: string,
	data?: any
): Promise<T> {
	const url = buildApiUrl(endpoint)
	
	const headers: Record<string, string> = {
		'Content-Type': 'application/json',
		'requesttoken': (window as any).OC?.requestToken || '',
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
	return ocsRequest<Task[]>('get', 'tasks')
}

/**
 * Create a new task
 * Converts date string to ISO 8601 format if needed
 */
export async function createTask(
	description: string,
	importance: number,
	dueDate: string
): Promise<Task> {
	// Convert YYYY-MM-DD to ISO 8601 format (YYYY-MM-DDTHH:mm:ss)
	// If already in ISO format, use as-is
	const formattedDueDate = dueDate.includes('T') 
		? dueDate 
		: `${dueDate}T00:00:00`
	
	return ocsRequest<Task>('post', 'tasks', {
		description,
		importance,
		dueDate: formattedDueDate,
	})
}

/**
 * Get a specific task by ID
 */
export async function getTask(id: number): Promise<Task> {
	return ocsRequest<Task>('get', `tasks/${id}`)
}

/**
 * Update an existing task
 * Converts date string to ISO 8601 format if needed
 */
export async function updateTask(
	id: number,
	data: {
		description?: string
		importance?: number
		dueDate?: string
	}
): Promise<Task> {
	// Convert YYYY-MM-DD to ISO 8601 format if provided
	const formattedData: any = {}
	
	if (data.description !== undefined) {
		formattedData.description = data.description
	}
	
	if (data.importance !== undefined) {
		formattedData.importance = data.importance
	}
	
	if (data.dueDate !== undefined) {
		formattedData.dueDate = data.dueDate.includes('T') 
			? data.dueDate 
			: `${data.dueDate}T00:00:00`
	}
	
	return ocsRequest<Task>('put', `tasks/${id}`, formattedData)
}

/**
 * Delete a task
 */
export async function deleteTask(id: number): Promise<void> {
	return ocsRequest<void>('delete', `tasks/${id}`)
}

/**
 * Convert API task to frontend task format
 * Adds a 'name' field that mirrors description for display purposes
 */
export function apiTaskToFrontendTask(apiTask: Task): any {
	return {
		id: apiTask.id,
		name: apiTask.description, // Map description to name for display
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
