define(function(require) {
    'use strict';

    const $ = require('jquery');
    const TableCellIterator = require('orodatagrid/js/datagrid/table-cell-iterator').default;
    const mockGid = `
        <style>
            [aria-rowindex] {
                display: flex;
            }
            [aria-rowindex] div:not([aria-colindex]){
                text-decoration: line-through
            }
            .hidden {
                display: none;
            }
        </style>
        <div role="grid">
            <div aria-rowindex="1">
                <div aria-colindex="1">(1,1)</div>
                <div aria-colindex="2">(1,2)</div>
                <div aria-colindex="3">(1,3)</div>
                <div aria-colindex="4">(1,4)</div>
            </div>
            <div aria-rowindex="2">
                <div aria-colindex="1">(2,1)</div>
                <div>(2,2)</div>
                <div aria-colindex="3" class="hidden">(2,3)</div>
                <div aria-colindex="4">(2,4)</div>
            </div>
            <div aria-rowindex="3" class="hidden">
                <div aria-colindex="1">(3,1)</div>
                <div aria-colindex="2">(3,2)</div>
                <div aria-colindex="3">(3,3)</div>
                <div aria-colindex="4">(3,4)</div>
            </div>
            <div aria-rowindex="4">
                <div aria-colindex="1">(4,1)</div>
                <div aria-colindex="2">(4,2)</div>
                <div aria-colindex="3">(4,3)</div>
                <div>(4,4)</div>
            </div>
        </div>`;

    describe('orodatagrid/js/datagrid/table-cell-iterator', function() {
        let tableCellIterator;
        let changeCurrentHandler;

        beforeEach(function() {
            window.setFixtures(mockGid);
            tableCellIterator = new TableCellIterator($('[role="grid"]'));
            changeCurrentHandler = jasmine.createSpy('changeCurrentHandler');
            tableCellIterator.on('change:current', changeCurrentHandler);
        });

        it('check constructor required arguments', function() {
            expect(() => new TableCellIterator()).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator([])).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator({})).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator($())).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator($(''))).toThrowError('Option "$table" is required');

            expect(() => new TableCellIterator($(mockGid))).not.toThrowError('Option "$table" is required');
        });

        it('check initialized state', function() {
            expect(tableCellIterator.$cell).toBeInstanceOf($);
            expect(tableCellIterator.$row).toBeInstanceOf($);
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
        });

        it('check "setCurrentCell" method', function() {
            const $previousCell = tableCellIterator.$cell;
            const $newCell = tableCellIterator.$row.find('[aria-colindex]:last');

            tableCellIterator.setCurrentCell($newCell);

            expect(tableCellIterator.$cell).toEqual($newCell);
            expect(changeCurrentHandler).toHaveBeenCalledOnceWith($newCell, $previousCell);
        });

        it('check "setCurrentCell" method with invalid arguments', function() {
            tableCellIterator.setCurrentCell($('<div></div>'));
            tableCellIterator.setCurrentCell($(''));
            tableCellIterator.setCurrentCell($(null));
            tableCellIterator.setCurrentCell($(void 0));

            expect(changeCurrentHandler).not.toHaveBeenCalled();
        });

        it('check "next" method', function() {
            let $previousCell = tableCellIterator.$cell;
            tableCellIterator.next();
            expect(tableCellIterator.index).toEqual([1, 2]);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            $previousCell = tableCellIterator.$cell;
            tableCellIterator.next();

            expect(tableCellIterator.index).toEqual([1, 3]);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            tableCellIterator.next();
            expect(tableCellIterator.index).toEqual([1, 4]);
            expect(changeCurrentHandler.calls.count()).toEqual(3);

            tableCellIterator.next();
            expect(tableCellIterator.index).toEqual([1, 4]);
            expect(changeCurrentHandler.calls.count()).toEqual(3);
        });

        it('check "prev" method', function() {
            tableCellIterator.prev();

            expect(tableCellIterator.index).toEqual([1, 1]);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.next();
            tableCellIterator.next();

            expect(tableCellIterator.index).toEqual([1, 3]);

            let $previousCell = tableCellIterator.$cell;

            tableCellIterator.prev();
            expect(tableCellIterator.index).toEqual([1, 2]);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            $previousCell = tableCellIterator.$cell;

            tableCellIterator.prev();
            expect(tableCellIterator.index).toEqual([1, 1]);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });

        it('check "firstInRow" method', function() {
            tableCellIterator.firstInRow();
            expect(tableCellIterator.index).toEqual([1, 1]);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.next();
            tableCellIterator.next();

            tableCellIterator.firstInRow();

            expect(tableCellIterator.index).toEqual([1, 1]);
        });

        it('check "lastInRow" method', function() {
            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.lastInRow();
            expect(tableCellIterator.index).toEqual([1, 4]);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            tableCellIterator.lastInRow();
            expect(tableCellIterator.index).toEqual([1, 4]);
            expect(changeCurrentHandler.calls.count()).toEqual(1);
        });

        it('check "nextRow" method', function() {
            tableCellIterator.nextRow();

            expect(tableCellIterator.index).toEqual([2, 1]);
            expect(changeCurrentHandler).toHaveBeenCalled();

            tableCellIterator.nextRow();
            // it's [4, 1] cell, cause 3rd row was skipped as hidden
            expect(tableCellIterator.index).toEqual([4, 1]);
            expect(changeCurrentHandler).toHaveBeenCalled();

            tableCellIterator.nextRow();

            expect(tableCellIterator.index).toEqual([4, 1]);
            expect(changeCurrentHandler.calls.count()).toEqual(2);
        });

        it('check "prevRow" method', function() {
            tableCellIterator.prevRow();

            expect(tableCellIterator.index).toEqual([1, 1]);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.nextRow();
            tableCellIterator.nextRow();
            expect(tableCellIterator.index).toEqual([4, 1]);

            tableCellIterator.prevRow();
            expect(tableCellIterator.index).toEqual([2, 1]);
        });

        it('check "firstRow" method', function() {
            tableCellIterator.firstRow();

            expect(tableCellIterator.index).toEqual([1, 1]);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.nextRow();
            tableCellIterator.nextRow();

            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.firstRow();

            expect(tableCellIterator.index).toEqual([1, 1]);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });

        it('check "lastRow" method', function() {
            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.lastRow();
            expect(tableCellIterator.index).toEqual([4, 1]);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });

        describe('skip element', function() {
            it('check "lastRow" and "lastInRow" methods', function() {
                tableCellIterator.lastRow();
                tableCellIterator.lastInRow();
                expect(tableCellIterator.index).toEqual([4, 3]);
            });

            it('check "lastInRow" and "lastRow" methods', function() {
                tableCellIterator.lastInRow();
                tableCellIterator.lastRow();
                expect(tableCellIterator.index).toEqual([2, 4]);
            });

            it('check "next" and "nextRow" methods', function() {
                tableCellIterator.next();
                tableCellIterator.nextRow();
                expect(tableCellIterator.index).toEqual([4, 2]);
            });

            it('check "lastInRow", "nextRow" and "prev" methods', function() {
                tableCellIterator.lastInRow();
                tableCellIterator.nextRow();
                tableCellIterator.prev();
                expect(tableCellIterator.index).toEqual([2, 1]);
            });

            it('check "lastRow", "next" and "prevRow" methods', function() {
                tableCellIterator.lastRow();
                tableCellIterator.next();
                tableCellIterator.prevRow();
                expect(tableCellIterator.index).toEqual([1, 2]);
            });

            it('check "nextRow" and "next" methods', function() {
                tableCellIterator.nextRow();
                tableCellIterator.next();
                expect(tableCellIterator.index).toEqual([2, 4]);
            });
        });
    });
});
