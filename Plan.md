# Eisenhower Board Nextcloud App - Implementation Plan

## Backend (PHP - lib/ directory)

1. **Database & Entity**
   - [ ] Create database migration for tasks table (user_id, description, importance 0-100, due_date)
   - [ ] Create Task entity/class with proper typing
   - [ ] Create Task mapper/repository for database operations

2. **API Endpoints** (in ApiController.php)
   - [ ] `GET /api/tasks` - List all tasks for current user
   - [ ] `POST /api/tasks` - Create a new task
   - [ ] `GET /api/tasks/{id}` - Get a specific task
   - [ ] `PUT /api/tasks/{id}` - Update a task
   - [ ] `DELETE /api/tasks/{id}` - Delete a task
   - [ ] Update OpenAPI spec in openapi.json to match

3. **Service Layer**
   - [ ] Create TaskService for business logic (position calculation, validation)
   - [ ] Implement positioning logic: x = log(daysUntilDue + 1), y = importance

## Frontend (Vue/TypeScript - src/ directory)

1. **Data Management**
   - [ ] Set up Axios/HTTP client for API calls
   - [ ] Create composables/stores for task state management
   - [ ] Implement real-time updates (polling or websockets)

2. **Components**
   - [ ] TaskBoard.vue - Main board component with SVG or absolute positioning
   - [ ] TaskItem.vue - Individual task display on the board
   - [ ] TaskForm.vue - Form for creating/editing tasks (description, importance slider, due date picker)
   - [ ] TaskList.vue - Optional list view of tasks

3. **Positioning Logic**
   - [ ] Calculate x/y coordinates from task data
   - [ ] Implement the board scaling (logarithmic x-axis, linear y-axis)
   - [ ] Handle drag-and-drop for manual adjustments (if needed)

4. **UI Updates**
   - [ ] Replace placeholder App.vue with actual board UI
   - [ ] Add proper styling for the board
   - [ ] Add buttons for add task, switch views, etc.

## Configuration & Setup

1. **App Registration**
   - [ ] Update Application.php boot() to register services
   - [ ] Configure dependency injection for TaskService, mapper, etc.

2. **Database Migration**
   - [ ] Create migration file in lib/Migration/
   - [ ] Register migration in Application.php

3. **Tests**
   - [ ] Update ApiTest.php with actual test cases
   - [ ] Add tests for positioning logic
   - [ ] Add tests for API endpoints
