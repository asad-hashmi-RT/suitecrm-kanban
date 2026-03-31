import { motion } from "framer-motion";
import { Calendar, DollarSign } from "lucide-react";
import type { CrmDeal } from "@/types/kanban";

const priorityStyles: Record<CrmDeal["priority"], string> = {
  high: "bg-destructive/10 text-destructive",
  medium: "bg-[hsl(var(--kanban-qualified)/0.12)] text-[hsl(var(--kanban-qualified))]",
  low: "bg-muted text-muted-foreground",
};

interface KanbanCardProps {
  deal: CrmDeal;
  onDragStart: (e: React.DragEvent, dealId: string) => void;
}

const KanbanCard = ({ deal, onDragStart }: KanbanCardProps) => {
  const formattedAmount = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 0,
  }).format(deal.amount);

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -8 }}
      draggable
      onDragStart={(e) => onDragStart(e as unknown as React.DragEvent, deal.id)}
      className="group cursor-grab rounded-lg border border-border bg-card p-4 active:cursor-grabbing"
      style={{ boxShadow: "var(--shadow-card)" }}
      whileHover={{ boxShadow: "var(--shadow-card-hover)", y: -1 }}
      transition={{ duration: 0.15 }}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0 flex-1">
          <h4 className="truncate text-base font-semibold text-card-foreground">{deal.title}</h4>
          <p className="mt-0.5 truncate text-sm text-muted-foreground">{deal.company}</p>
        </div>
        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
          {deal.avatarInitials}
        </div>
      </div>

      <div className="mt-3 flex items-center gap-3">
        <span className="inline-flex items-center gap-1 text-sm font-medium text-card-foreground">
          <DollarSign className="h-3 w-3 text-muted-foreground" />
          {formattedAmount}
        </span>
        <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
          <Calendar className="h-3 w-3" />
          {new Date(deal.date).toLocaleDateString("en-US", { month: "short", day: "numeric" })}
        </span>
      </div>

      <div className="mt-2.5">
        <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${priorityStyles[deal.priority]}`}>
          {deal.priority}
        </span>
      </div>
    </motion.div>
  );
};

export default KanbanCard;
