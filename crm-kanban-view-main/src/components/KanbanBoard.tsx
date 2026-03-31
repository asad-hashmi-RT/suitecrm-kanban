import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { LayoutGrid, RefreshCw } from "lucide-react";
import KanbanColumnComponent from "./KanbanColumn";
import type { KanbanColumn } from "@/types/kanban";
import { api } from "@/services/api";
import { filterColumnsBySearch, moveDealBetweenColumns, type DragItem } from "@/lib/kanban";

const KanbanBoard = () => {
  const [columns, setColumns] = useState<KanbanColumn[]>([]);
  const [search, setSearch] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [isSavingMove, setIsSavingMove] = useState(false);
  const [moveError, setMoveError] = useState<string | null>(null);
  const dragItem = useRef<DragItem | null>(null);

  const loadBoard = useCallback(async () => {
    setIsLoading(true);
    setLoadError(null);
    try {
      const boardColumns = await api.loadBoard();
      setColumns(boardColumns);
    } catch (error) {
      console.error("Unable to load Kanban data", error);
      setLoadError("Could not load leads. Please try again.");
      setColumns([]);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadBoard();
  }, [loadBoard]);

  const handleDragStart = useCallback((_e: React.DragEvent, dealId: string, fromColumnId: string) => {
    dragItem.current = { dealId, fromColumnId };
  }, []);

  const handleDrop = useCallback(async (_e: React.DragEvent, toColumnId: string) => {
    if (!dragItem.current || isSavingMove) return;
    const { dealId, fromColumnId } = dragItem.current;
    if (fromColumnId === toColumnId) {
      dragItem.current = null;
      return;
    }

    setMoveError(null);
    const previousColumns = columns;
    const nextColumns = moveDealBetweenColumns(columns, dealId, fromColumnId, toColumnId);
    setColumns(nextColumns);
    setIsSavingMove(true);

    try {
      await api.moveLead({ leadId: dealId, toStatus: toColumnId });
    } catch (error) {
      console.error("Unable to move lead", error);
      setColumns(previousColumns);
      setMoveError("Status update failed. Please try again.");
    } finally {
      setIsSavingMove(false);
      dragItem.current = null;
    }
  }, [columns, isSavingMove]);

  const filteredColumns = useMemo(
    () => filterColumnsBySearch(columns, search),
    [columns, search]
  );

  const totalDeals = columns.reduce((sum, c) => sum + c.deals.length, 0);
  const hasAnyDeals = totalDeals > 0;
  const hasFilteredResults = filteredColumns.some((column) => column.deals.length > 0);
  const showEmptyState = !isLoading && !loadError && !hasAnyDeals;
  const showNoSearchResults = !isLoading && !loadError && hasAnyDeals && !hasFilteredResults && search.trim() !== "";

  return (
    <div className="kanban-suite-root flex min-h-screen w-full flex-col bg-background">
      {/* Top bar */}
      <header className="border-b border-border bg-card px-6 py-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
              <LayoutGrid className="h-4.5 w-4.5" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-foreground">Sales Pipeline</h1>
              <p className="text-sm text-muted-foreground">{totalDeals} deals across {columns.length} stages</p>
            </div>
          </div>

          <div className="flex flex-1 items-center gap-2 sm:max-w-md">
            <div className="relative flex-1">
              <input
                type="text"
                placeholder="Search deals..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/30"
              />
            </div>
            <button
              type="button"
              onClick={loadBoard}
              disabled={isLoading}
              className="inline-flex h-9 items-center gap-2 rounded-lg border border-input bg-background px-3 text-sm text-foreground hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
              title="Refresh board"
            >
              <RefreshCw className={`h-4 w-4 ${isLoading ? "animate-spin" : ""}`} />
              <span className="hidden sm:inline">Refresh</span>
            </button>
          </div>
        </div>
      </header>

      {/* Board */}
      <div className="flex-1 overflow-x-auto p-6">
        {loadError && (
          <div className="mb-4 rounded-lg border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
            {loadError}{" "}
            <button type="button" onClick={loadBoard} className="underline underline-offset-4">
              Retry
            </button>
          </div>
        )}
        {moveError && !loadError && (
          <div className="mb-4 rounded-lg border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
            {moveError}
          </div>
        )}
        {isLoading && !loadError && (
          <div className="mb-4 rounded-lg border border-border bg-card px-3 py-2 text-sm text-muted-foreground">
            Loading leads...
          </div>
        )}
        {isSavingMove && (
          <div className="mb-4 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-sm text-foreground">
            Updating lead status...
          </div>
        )}
        {showEmptyState && (
          <div className="mb-4 rounded-lg border border-border bg-card px-3 py-2 text-sm text-muted-foreground">
            No leads found for the configured Kanban statuses.
          </div>
        )}
        {showNoSearchResults && (
          <div className="mb-4 rounded-lg border border-border bg-card px-3 py-2 text-sm text-muted-foreground">
            No leads match your search.
          </div>
        )}
        <div className="flex gap-4 pb-2">
          {filteredColumns.map((col) => (
            <KanbanColumnComponent
              key={col.id}
              column={col}
              onDragStart={handleDragStart}
              onDrop={handleDrop}
            />
          ))}
        </div>
      </div>
    </div>
  );
};

export default KanbanBoard;
