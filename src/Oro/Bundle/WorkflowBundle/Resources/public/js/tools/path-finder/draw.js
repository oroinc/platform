function draw(html, dimensions) {
    let board = document.getElementById('drawing-overlay');
    if (!board) {
        document.querySelector('.workflow-flowchart-editor, .workflow-flowchart')
            .insertAdjacentHTML('beforeEnd',
                '<div style="position: absolute; width: 100%; height: 100%; top: 0; z-index: 100000;" ' +
                'id="drawing-overlay"></div>');
    }
    board = document.getElementById('drawing-overlay');
    if (dimensions) {
        const style = [];
        for (const name in dimensions) {
            if (dimensions.hasOwnProperty(name)) {
                style.push(name + ': ' + dimensions[name] + 'px');
            }
        }
        style.push('position: absolute');
        html = '<svg style="' + style.join('; ') + '">' + html + '</svg>';
    }
    board.insertAdjacentHTML('beforeEnd', html);
}

draw.clear = function() {
    const board = document.getElementById('drawing-overlay');
    if (board) {
        board.remove();
    }
};

draw.line = function(from, to, color) {
    const minX = Math.min(from.x, to.x);
    const minY = Math.min(from.y, to.y);
    const normAX = from.x === minX ? to.x - minX : 0;
    const normBX = to.x === minX ? from.x - minX : 0;
    const normAY = from.y === minY ? to.y - minY : 0;
    const normBY = to.y === minY ? from.y - minY : 0;
    draw('<path stroke-width="1" stroke="' + color +
        '" fill="none" d="M ' + normAX + ' ' + normAY + ' L ' + normBX + ' ' + normBY +
        '"></path>', {
        top: minY, left: minX, width: Math.abs(normAX - normBX) + 1, height: Math.abs(normAY - normBY) + 1});
};

draw.circle = function(point, radius, color) {
    draw(
        '<circle fill="' + color + '" r="' + radius + '" cx="' + radius + '" cy="' + radius + '"></circle>',
        {top: point.y - radius, left: point.x - radius}
    );
};

draw.text = function(point, color, text) {
    draw('<svg style="position:absolute;width:1000px;height:1000px;">' +
        '<text x="' + point.x + '" y="' + point.y + '" fill="' + color + '" font-size="10">' +
        text + '</text></svg>');
};

export default draw;
