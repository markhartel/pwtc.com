/* global jQuery wp RMLWpIs */

/**
 * Change the AllInOneTree for the normal based WP List Table.
 */
window.rml.hooks.register("aioSettings/list", function(settings, $) {
    settings.others = settings.others || { };
});