export interface CrmDeal {
  id: string;
  title: string;
  company: string;
  email?: string;
  amount: number;
  date: string;
  priority: "low" | "medium" | "high";
  assignee: string;
  avatarInitials: string;
}

export interface KanbanColumn {
  id: string;
  title: string;
  colorVar: string;
  deals: CrmDeal[];
}

export interface BoardFilters {
  assignedUserId?: string;
  search?: string;
}

