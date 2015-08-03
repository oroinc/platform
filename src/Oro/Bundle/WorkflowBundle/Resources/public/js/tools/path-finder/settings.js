define(function() {
    'use strict';
    var settings = {};
    settings.recommendedConnectionWidth = 12;
    settings.cornerCost = 200;
    settings.optimizationCornerCost = 199; // recomended value = cornerCost - 1
    settings.crossPathCost = 300; // recomended value = cornerCost * 1.5
    // static turnPreference: number = -1; // right turn
    settings.centerAxisCostMultiplier = 0.99;
    settings.usedAxisCostMultiplier = 0.99;
    settings.overBlockLineCostMultiplier = 20;
    return settings;
});
