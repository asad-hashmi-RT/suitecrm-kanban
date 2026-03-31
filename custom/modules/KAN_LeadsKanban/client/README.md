# React Build Placement

Place the React production build output in:

- `custom/modules/KAN_LeadsKanban/client/dist/`

Expected structure:

- `custom/modules/KAN_LeadsKanban/client/dist/.vite/manifest.json` (preferred)
- `custom/modules/KAN_LeadsKanban/client/dist/assets/<hashed>.js`
- `custom/modules/KAN_LeadsKanban/client/dist/assets/<hashed>.css`

Build and copy steps from `crm-kanban-view-main`:

1. `npm install`
2. `npm run build`
3. Copy `crm-kanban-view-main/dist/*` into `custom/modules/KAN_LeadsKanban/client/dist/`

The custom view auto-loads assets from Vite manifest when available.

