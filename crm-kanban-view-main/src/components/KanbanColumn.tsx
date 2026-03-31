import { useState } from "react";
import { AnimatePresence } from "framer-motion";
import KanbanCard from "./KanbanCard";
import type { KanbanColumn as ColumnType } from "@/types/kanban";

interface KanbanColumnProps {
  column: ColumnType;
  onDragStart: (e: React.DragEvent, dealId: string, fromColumnId: string) => void;
  onDrop: (e: React.DragEvent, toColumnId: string) => void;
}

const KanbanColumnComponent = ({ column, onDragStart, onDrop }: KanbanColumnProps) => {
  const [isDragOver, setIsDragOver] = useState(false);

  const totalAmount = column.deals.reduce((sum, d) => sum + d.amount, 0);
  const formattedTotal = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    minimumFractionDigits: 0,
  }).format(totalAmount);

  return (
    <div
      className={`flex w-[88vw] max-w-[22rem] shrink-0 flex-col rounded-xl border border-border/60 p-3 transition-colors duration-150 sm:w-80 ${
        isDragOver ? "bg-primary/5" : "bg-card/30"
      }`}
      onDragOver={(e) => { e.preventDefault(); setIsDragOver(true); }}
      onDragLeave={() => setIsDragOver(false)}
      onDrop={(e) => { e.preventDefault(); setIsDragOver(false); onDrop(e, column.id); }}
    >
      {/* Header */}
      <div className="mb-3 flex items-center gap-2.5 px-1">
        <div className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: `hsl(${column.colorVar})` }} />
        <h3 className="text-base font-semibold text-foreground">{column.title}</h3>
        <span className="ml-auto rounded-full bg-secondary px-2 py-0.5 text-sm font-medium text-muted-foreground">
          {column.deals.length}
        </span>
      </div>

      {/* Total */}
      <div className="mb-3 px-1">
        <span className="text-sm font-medium text-muted-foreground">{formattedTotal}</span>
      </div>

      {/* Cards */}
      <div className="flex min-h-[160px] flex-1 flex-col gap-2.5 px-0.5">
        <AnimatePresence mode="popLayout">
          {column.deals.map((deal) => (
            <KanbanCard
              key={deal.id}
              deal={deal}
              onDragStart={(e, dealId) => onDragStart(e, dealId, column.id)}
            />
          ))}
        </AnimatePresence>
      </div>
    </div>
  );
};

export default KanbanColumnComponent;
