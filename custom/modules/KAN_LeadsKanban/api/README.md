# Leads Kanban API (SuiteCRM Custom Entry Point)

This API is served from:

- `index.php?entryPoint=LeadsKanbanApi`

It supports REST-style routes via `route`:

- `GET /leads-kanban`
- `PATCH /leads-kanban/:id`

## Example requests

### 1) Get all leads grouped by status

```bash
curl -X GET \
  "http://localhost/suitecrm_kanban/index.php?entryPoint=LeadsKanbanApi&route=/leads-kanban" \
  -H "Cookie: PHPSESSID=<session-id>"
```

### 2) Get grouped leads with filters

```bash
curl -X GET \
  "http://localhost/suitecrm_kanban/index.php?entryPoint=LeadsKanbanApi&route=/leads-kanban&assigned_user_id=1&q=john" \
  -H "Cookie: PHPSESSID=<session-id>"
```

### 3) Update lead status (drag-drop)

```bash
curl -X PATCH \
  "http://localhost/suitecrm_kanban/index.php?entryPoint=LeadsKanbanApi&route=/leads-kanban/<lead-id>" \
  -H "Cookie: PHPSESSID=<session-id>" \
  -H "Content-Type: application/json" \
  -d '{"to_status":"in_process"}'
```

`to_status` accepts either:
- Frontend column id: `new`, `assigned`, `in_process`, `converted`, `dead`
- Exact Leads status value: `New`, `Assigned`, `In Process`, `Converted`, `Dead`

## Example Leads query behavior

The service builds a filtered Leads query equivalent to:

```sql
SELECT *
FROM leads
WHERE leads.deleted = 0
  AND leads.status IN ('New', 'Assigned', 'In Process', 'Converted', 'Dead')
  AND leads.assigned_user_id = '<optional assigned_user_id>'
  AND (
    leads.first_name LIKE '%<q>%'
    OR leads.last_name LIKE '%<q>%'
    OR CONCAT(IFNULL(leads.first_name, ''), ' ', IFNULL(leads.last_name, '')) LIKE '%<q>%'
    OR leads.email1 LIKE '%<q>%'
  )
ORDER BY leads.date_modified DESC;
```

In implementation, SuiteCRM bean APIs are used (`BeanFactory::newBean('Leads')->get_full_list(...)`) to stay within framework security patterns.

## ACL/permissions

- Module-level ACL check:
  - `list` permission required for `GET`
  - `edit` permission required for `PATCH`
- Record-level ACL check:
  - `view` + `save` required for status update

If access is denied, API returns `403`.

