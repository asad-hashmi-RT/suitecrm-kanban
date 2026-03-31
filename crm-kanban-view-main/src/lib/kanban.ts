import type { KanbanColumn } from "@/types/kanban";

export interface DragItem {
  dealId: string;
  fromColumnId: string;
}

export const moveDealBetweenColumns = (
  columns: KanbanColumn[],
  dealId: string,
  fromColumnId: string,
  toColumnId: string
): KanbanColumn[] => {
  if (fromColumnId === toColumnId) return columns;

  const fromColumn = columns.find((column) => column.id === fromColumnId);
  const deal = fromColumn?.deals.find((item) => item.id === dealId);
  if (!deal) return columns;

  return columns.map((column) => {
    if (column.id === fromColumnId) {
      return { ...column, deals: column.deals.filter((item) => item.id !== dealId) };
    }
    if (column.id === toColumnId) {
      return { ...column, deals: [...column.deals, deal] };
    }
    return column;
  });
};

export const filterColumnsBySearch = (columns: KanbanColumn[], search: string): KanbanColumn[] => {
  const term = search.trim().toLowerCase();
  if (!term) return columns;

  return columns.map((column) => ({
    ...column,
    deals: column.deals.filter(
      (deal) =>
        deal.title.toLowerCase().includes(term) ||
        deal.company.toLowerCase().includes(term) ||
        (deal.email || "").toLowerCase().includes(term)
    ),
  }));
};

