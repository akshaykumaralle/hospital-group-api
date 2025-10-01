# Hospital & Clinician Group Management API Documentation

This API provides a RESTful interface for managing a hierarchical tree structure of hospitals and clinician groups.

## Base URL

`/api`

## 1. Create a New Group (Hospital or Clinician Group)

| **Method** | **Endpoint** | **Description**                                                                        |
| :--------- | :----------- | :------------------------------------------------------------------------------------- |
| `POST`     | `/groups`    | Creates a new group. Can be a top-level hospital (`parent_id: null`) or a child group. |

### Request Body (JSON)

| **Field**     | **Type** | **Required** | **Description**                              | **Constraints**                                     |
| :------------ | :------- | :----------- | :------------------------------------------- | :-------------------------------------------------- |
| `name`        | string   | Yes          | The name of the hospital or clinician group. | **Unique** under the same `parent_id`.              |
| `description` | string   | No           | A description of the group.                  |                                                     |
| `type`        | string   | Yes          | The type of the group.                       | Must be `hospital` or `clinician_group`.            |
| `parent_id`   | integer  | No           | The ID of the parent group (must exist).     | Must be a valid ID in the `groups` table or `null`. |

### Success Response (201 Created)

Returns the newly created group object.
```json
{
    "id": 1,
    "name": "Main Hospital",
    "description": "Primary surgical facility.",
    "type": "hospital",
    "parent_id": null,
    "created_at": "2023-10-27T10:00:00.000000Z"
}
```

### Error Responses

| **Status**                 | **Description**                                                                                                                      |
| :------------------------- | :----------------------------------------------------------------------------------------------------------------------------------- |
| `422 Unprocessable Entity` | Missing required field (`name`, `type`), invalid `parent_id`, or a group with this `name` already exists under the same `parent_id`. |

## 2. Retrieve All Groups (Tree Structure)

| **Method** | **Endpoint** | **Description**                                                                |
| :--------- | :----------- | :----------------------------------------------------------------------------- |
| `GET`      | `/groups`    | Retrieves the entire group structure, rooted at groups with `parent_id: null`. |

### Success Response (200 OK)

Returns an array of top-level groups, with their children nested recursively under the `children` key.
```json
[
    {
        "id": 1,
        "name": "Main Hospital",
        "type": "hospital",
        "parent_id": null,
        "children": [
            {
                "id": 2,
                "name": "Cardiology",
                "type": "clinician_group",
                "parent_id": 1,
                "children": []
            }
            // ... more nested children
        ]
    }
    // ... next top-level group
]
```

### Error Responses

| **Status**                  | **Description**                                                                            |
| :-------------------------- | :----------------------------------------------------------------------------------------- |
| `500 Internal Server Error` | Database connection error or service unavailability (as handled by the Exception Handler). |

## 3. Retrieve a Specific Group

| **Method** | **Endpoint**   | **Description**                                                         |
| :--------- | :------------- | :---------------------------------------------------------------------- |
| `GET`      | `/groups/{id}` | Retrieves the details of a single group, including its direct children. |

### Success Response (200 OK)

Returns the group object with the full details, including its direct children.
```json
{
    "id": 1,
    "name": "Main Hospital",
    "description": "Primary surgical facility.",
    "type": "hospital",
    "parent_id": null,
    "children": [
    // Direct children of group 1
    ]
}
```

### Error Responses

| **Status**      | **Description**                                   | **Response Payload**                                                   |
| :-------------- | :------------------------------------------------ | :--------------------------------------------------------------------- |
| `404 Not Found` | The group ID specified in the URL does not exist. | `{"status": "error", "message": "The requested group was not found."}` |

## 4. Update a Group

| **Method** | **Endpoint**   | **Description**                           |
| :--------- | :------------- | :---------------------------------------- |
| `PUT`      | `/groups/{id}` | Updates the details of an existing group. |

### Request Body (JSON)

Accepts any subset of the fields used in the `POST` request.

| **Field**     | **Type** | **Description**                    |
| :------------ | :------- | :--------------------------------- |
| `name`        | string   | New name for the group.            |
| `description` | string   | New description.                   |
| `type`        | string   | New type for the group.            |
| `parent_id`   | integer  | New parent ID (to move the group). |

### Success Response (200 OK)

Returns the updated group object.

### Error Responses

| **Status**                 | **Description**                                                                                                                                                                                                                     | **Response Payload**                                                                                    |
| :------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------ |
| `404 Not Found`            | The group ID does not exist.                                                                                                                                                                                                        | `{"status": "error", "message": "The requested group was not found."}`                                  |
| `422 Unprocessable Entity` | **Validation/Integrity Error:** <br>1. Renaming the group to a name that already exists under the same parent. <br>2. **Circular Reference:** Attempting to set the parent to the group itself or one of its descendants/ancestors. | **Circular Reference Example:** `{"status": "error", "message": "A group cannot be its own ancestor."}` |

## 5. Delete a Group

| **Method** | **Endpoint**   | **Description**              |
| :--------- | :------------- | :--------------------------- |
| `DELETE`   | `/groups/{id}` | Deletes the specified group. |

### Success Response (200 OK)

Returns a success message upon deletion.

```json
{
    "status": "success",
    "message": "Group deleted successfully"
}
```


### Error Responses

| **Status**      | **Description**                                                                                                                               | **Response Payload**                                                   |
| :-------------- | :-------------------------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------- |
| `404 Not Found` | The group ID does not exist.                                                                                                                  | `{"status": "error", "message": "The requested group was not found."}` |
| `409 Conflict`  | **Data Integrity Constraint:** The group cannot be deleted because it still has one or more child groups. All children must be deleted first. | `{"status": "error", "message": "Cannot delete group with children."}` |