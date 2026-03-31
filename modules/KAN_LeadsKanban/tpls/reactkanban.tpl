{literal}
<style>
  .kanban-host #root {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
    zoom: 1.25;
    transform-origin: top left;
  }

  /* Counter SuiteCRM theme overrides inside embedded React app */
  .kanban-host .kanban-suite-root {
    font-size: 16px !important;
    line-height: 1.4 !important;
  }

  .kanban-host {
    overflow: auto;
  }

  .kanban-host .kanban-suite-root input[type="text"] {
    background-image: none !important;
    -webkit-appearance: none;
    appearance: none;
  }

  .kanban-host .kanban-suite-root svg {
    vertical-align: middle;
  }
</style>
{/literal}

<div class="kanban-host">
  <div class="moduleTitle">
    <h2>{$MODULE_TITLE}</h2>
  </div>

  <div id="root"></div>
</div>

<script>
  window.__KANBAN_CONFIG__ = {$REACT_CONFIG_JSON nofilter};
</script>

{if $REACT_ENTRY_CSS neq ""}
  <link rel="stylesheet" href="{$REACT_ENTRY_CSS}">
{/if}

{if $REACT_ENTRY_JS neq ""}
  <script type="module" src="{$REACT_ENTRY_JS}"></script>
{else}
  <div class="alert alert-warning" style="margin-top: 12px;">
    React build assets not found. Place build in:
    <code>custom/modules/KAN_LeadsKanban/client/dist/</code>
  </div>
{/if}

