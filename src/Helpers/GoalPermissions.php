<?php

/**
 * Checks if a given uploader user is an ancestor (manager in hierarchy) of the assignee user.
 * Includes self-assignment and special admin permissions.
 *
 * @param int $uploaderId The ID of the user attempting to assign the goal.
 * @param int $assigneeId The ID of the user the goal is being assigned to.
 * @param UserRepository $userRepo
 * @param DepartmentRepository $departmentRepo
 * @return bool True if uploader is an ancestor or admin, false otherwise.
 */
function is_ancestor_manager(int $uploaderId, int $assigneeId, UserRepository $userRepo, DepartmentRepository $departmentRepo, Auth $auth): bool
{
    // 1. Admin users have universal permission
    if ($auth->isAdmin()) {
        return true;
    }

    // 2. Self-assignment is always allowed
    if ($uploaderId === $assigneeId) {
        return true;
    }

    // 3. Check if uploader is an ancestor in the hierarchy
    // Get all departments the assignee belongs to
    $assigneeUser = $userRepo->find($assigneeId); // This returns user with department_ids
    if (!$assigneeUser || empty($assigneeUser['department_ids'])) {
        // Assignee not found or not assigned to any department.
        // If they are unassigned, only admin can assign to them (already handled), or self-assign (already handled).
        return false;
    }

    $allDepartments = $departmentRepo->findAll();
    $departmentsById = [];
    foreach ($allDepartments as $dept) {
        $departmentsById[$dept['id']] = $dept;
    }

    foreach ($assigneeUser['department_ids'] as $assigneeDeptId) {
        $currentDeptId = $assigneeDeptId;
        // Traverse up the hierarchy from the assignee's department
        while ($currentDeptId !== null && isset($departmentsById[$currentDeptId])) {
            $currentDept = $departmentsById[$currentDeptId];

            // Is the current department managed by the uploader?
            if ($currentDept['manager_id'] === $uploaderId) {
                return true; // Uploader manages a department in the assignee's chain
            }

            // Move up to the parent department
            $currentDeptId = $currentDept['parent_id'];
        }
    }

    return false; // Uploader is not an ancestor in any of the assignee's department chains
}