<?php
/**
 * Full-page host view for the React Leads Kanban app.
 *
 * This view:
 * - enforces permissions
 * - injects runtime config for frontend API calls
 * - resolves Vite build assets from manifest
 */
require_once 'include/MVC/View/SugarView.php';

class KAN_LeadsKanbanViewReactkanban extends SugarView
{
    public function display()
    {
        global $current_user, $sugar_config;

        if (!ACLController::checkAccess('Leads', 'list', true)) {
            ACLController::displayNoAccess(true);
            sugar_cleanup(true);
        }

        $distDir = 'custom/modules/KAN_LeadsKanban/client/dist';
        $manifestPath = $distDir . '/.vite/manifest.json';
        $entryCss = '';
        $entryJs = '';

        // Prefer manifest-driven asset resolution (supports hashed chunk names).
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (is_array($manifest)) {
                $entry = $this->resolveManifestEntry($manifest);
                if (is_array($entry) && !empty($entry['file'])) {
                    $entryJs = $distDir . '/' . ltrim($entry['file'], '/');
                }
                if (is_array($entry) && !empty($entry['css']) && is_array($entry['css'])) {
                    $entryCss = $distDir . '/' . ltrim($entry['css'][0], '/');
                }
            }
        }

        // Fallback for non-hashed build output.
        if ($entryJs === '' && file_exists($distDir . '/assets/index.js')) {
            $entryJs = $distDir . '/assets/index.js';
        }
        if ($entryCss === '' && file_exists($distDir . '/assets/index.css')) {
            $entryCss = $distDir . '/assets/index.css';
        }
        // Fallback for hashed Vite output when manifest is unavailable:
        // read dist/index.html and extract script/link asset paths.
        if (($entryJs === '' || $entryCss === '') && file_exists($distDir . '/index.html')) {
            $html = file_get_contents($distDir . '/index.html');
            if (is_string($html)) {
                if ($entryJs === '' && preg_match('/<script[^>]+type="module"[^>]+src="([^"]+)"/i', $html, $jsMatch)) {
                    $entryJs = $distDir . '/' . ltrim($jsMatch[1], '/');
                }
                if ($entryCss === '' && preg_match('/<link[^>]+rel="stylesheet"[^>]+href="([^"]+)"/i', $html, $cssMatch)) {
                    $entryCss = $distDir . '/' . ltrim($cssMatch[1], '/');
                }
            }
        }

        $reactConfig = array(
            'apiBase' => 'index.php?entryPoint=LeadsKanbanApi',
            'siteUrl' => isset($sugar_config['site_url']) ? $sugar_config['site_url'] : '',
            'currentUser' => array(
                'id' => isset($current_user->id) ? $current_user->id : '',
                'name' => isset($current_user->full_name) ? $current_user->full_name : '',
            ),
        );

        $this->ss->assign('MODULE_TITLE', 'Leads Kanban');
        $this->ss->assign('REACT_CONFIG_JSON', json_encode($reactConfig));
        $this->ss->assign('REACT_ENTRY_JS', $entryJs);
        $this->ss->assign('REACT_ENTRY_CSS', $entryCss);
        $this->ss->display('custom/modules/KAN_LeadsKanban/tpls/reactkanban.tpl');
    }

    /**
     * Resolve the best manifest entry for Vite app bootstrap.
     */
    private function resolveManifestEntry($manifest)
    {
        if (isset($manifest['index.html'])) {
            return $manifest['index.html'];
        }

        foreach ($manifest as $entry) {
            if (is_array($entry) && !empty($entry['isEntry'])) {
                return $entry;
            }
        }

        return null;
    }
}

