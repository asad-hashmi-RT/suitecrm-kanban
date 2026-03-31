const DEFAULT_COLUMNS = [
  { id: "new", title: "New Leads", colorVar: "var(--kanban-new)" },
  { id: "assigned", title: "Assigned", colorVar: "var(--kanban-contacted)" },
  { id: "in_process", title: "In Process", colorVar: "var(--kanban-qualified)" },
  { id: "converted", title: "Converted", colorVar: "var(--kanban-proposal)" },
  { id: "dead", title: "Dead", colorVar: "var(--kanban-closed)" },
];

const API_ENTRYPOINT = "index.php?entryPoint=LeadsKanbanApi";
const ROUTES = {
  board: "/leads-kanban",
  moveLead: (leadId) => `/leads-kanban/${leadId}`,
};

const getRuntimeConfig = () => {
  if (typeof window === "undefined") return {};
  return window.__KANBAN_CONFIG__ || {};
};

const resolveApiBase = () => {
  const runtimeConfig = getRuntimeConfig();

  if (runtimeConfig.apiBase) {
    // Resolve relative apiBase (index.php?...) against current SuiteCRM URL.
    return new URL(runtimeConfig.apiBase, window.location.href);
  }

  // Fallback: resolve against current page URL, not origin, to preserve subdirectory installs.
  return new URL(API_ENTRYPOINT, window.location.href);
};

const buildUrl = (route, params = {}) => {
  const url = resolveApiBase();
  url.searchParams.set("route", route);
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && `${value}`.trim() !== "") {
      url.searchParams.set(key, `${value}`);
    }
  });
  return url.toString();
};

const normalizeDeal = (lead = {}) => ({
  id: lead.id,
  title: lead.full_name || lead.title || "Unnamed Lead",
  company: lead.company || lead.account_name || "-",
  email: lead.email || lead.email1 || "",
  amount: Number(lead.amount || 0),
  date: lead.date_modified || lead.date || new Date().toISOString(),
  priority: lead.priority || "low",
  assignee: lead.assigned_user_name || lead.assignee || "--",
  avatarInitials: lead.avatar_initials || (lead.assigned_user_name || "U").slice(0, 2).toUpperCase(),
});

const normalizeBoard = (payload = {}) => {
  const payloadColumns = Array.isArray(payload.columns) ? payload.columns : [];
  const byId = new Map(payloadColumns.map((column) => [column.id, column]));

  return DEFAULT_COLUMNS.map((column) => {
    const match = byId.get(column.id);
    const cards = Array.isArray(match?.cards) ? match.cards : [];

    return {
      ...column,
      deals: cards.map(normalizeDeal),
    };
  });
};

export const api = {
  async loadBoard(filters = {}) {
    const response = await fetch(buildUrl(ROUTES.board, filters), {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });

    if (!response.ok) {
      throw new Error(`Failed to load Kanban board (${response.status})`);
    }

    const data = await response.json();
    return normalizeBoard(data);
  },

  async moveLead({ leadId, toStatus }) {
    const response = await fetch(buildUrl(ROUTES.moveLead(leadId)), {
      method: "PATCH",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json;charset=UTF-8",
      },
      body: JSON.stringify({ to_status: toStatus }),
    });

    if (!response.ok) {
      throw new Error(`Failed to move lead (${response.status})`);
    }

    return response.json();
  },
};

