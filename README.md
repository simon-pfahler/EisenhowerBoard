# Eisenhower Task Board

A Nextcloud app that provides a visual task board where tasks are automatically positioned based on their importance and due date.

**DISCLAIMER**:
This app is currently in early development and not ready for any kind of use!!!

## Features

- **Task Management**: Create, read, update, and delete tasks with description, importance (0-100), and due date
- **Visual Board**: Tasks are automatically positioned on a 2D board:
  - **X-axis**: Due date using logarithmic scale (earlier dates on the left, later on the right)
  - **Y-axis**: Importance (0-100, with 100 at the top)
- **Dynamic Updates**: Task positions update in real-time as due dates approach
- **User-Specific**: Each user has their own set of tasks

## Installation

### Manual Installation

1. Clone or download this repository into your Nextcloud's `apps` directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/simon-pfahler/EisenhowerBoard.git eisenhowerboard
   ```

2. Enable the app in Nextcloud:
   ```bash
   cd /path/to/nextcloud
   php occ app:enable eisenhowerboard
   ```

4. The app should now appear in your Nextcloud apps menu as "Eisenhower Board"

## Usage

1. Navigate to the Eisenhower Board app from your Nextcloud dashboard
2. Click "Add Task" to create a new task
3. Enter:
   - **Description**: What the task is about
   - **Importance**: A value from 0-100 (higher = more important)
   - **Due Date**: When the task is due
4. The task will automatically appear on the board at the appropriate position
5. Click on a task to edit or delete it
6. Drag tasks on the board to adjust their position

## Task Positioning Logic

The board uses a logarithmic scale for the x-axis (due date) and a linear scale for the y-axis (importance):

- **X-axis (Due Date)**: Uses `log(daysUntilDue + 1)` to compress the time scale. This means:
  - Tasks due very soon appear on the far left
  - Tasks due in the near future are spread out
  - Tasks due far in the future are compressed towards the right
  
- **Y-axis (Importance)**: Linear scale from 0 (bottom) to 100 (top)

This creates an Eisenhower Matrix-like visualization where:
- Top-left: Urgent and important tasks
- Top-right: Important but not urgent tasks
- Bottom-left: Urgent but not important tasks
- Bottom-right: Neither urgent nor important tasks

## License

AGPL-3.0-or-later - See [LICENSE](LICENSE) file for details.
