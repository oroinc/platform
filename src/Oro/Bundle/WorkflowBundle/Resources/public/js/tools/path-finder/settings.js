define(function() {
    'use strict';
    var settings = {};
    settings.recommendedConnectionWidth = 12;
    settings.cornerCost = 100;
    settings.optimizationCornerCost = 99; // recomended value = cornerCost - 1
    settings.crossPathCost = 99; // recomended value = cornerCost - 1
    // static turnPreference: number = -1; // right turn
    settings.centerAxisCostMultiplier = 0.98;
    settings.usedAxisCostMultiplier = 0.99;
    settings.overBlockLineCostMultiplier = 20;
    return settings;
});
