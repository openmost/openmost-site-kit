/**
 * DataTable Component
 * Reusable table with search, sortable columns, and pagination.
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

const ROWS_PER_PAGE = 10;

const DataTable = ({ columns, data, defaultSort = { key: null, dir: 'desc' } }) => {
    const [search, setSearch] = useState('');
    const [sort, setSort] = useState(defaultSort);
    const [page, setPage] = useState(0);

    const handleSearch = (value) => {
        setSearch(value);
        setPage(0);
    };

    const handleSort = (key) => {
        setSort((prev) => {
            if (prev.key === key) {
                return { key, dir: prev.dir === 'asc' ? 'desc' : 'asc' };
            }
            return { key, dir: 'desc' };
        });
        setPage(0);
    };

    const processed = useMemo(() => {
        let rows = data || [];

        // Search using searchValue or raw data key — never rendered JSX.
        if (search.trim()) {
            const q = search.trim().toLowerCase();
            rows = rows.filter((row) =>
                columns.some((col) => {
                    const val = col.searchValue ? col.searchValue(row) : row[col.key];
                    return String(val ?? '').toLowerCase().includes(q);
                })
            );
        }

        if (sort.key) {
            const col = columns.find((c) => c.key === sort.key);
            rows = [...rows].sort((a, b) => {
                let aVal = col?.sortValue ? col.sortValue(a) : a[sort.key];
                let bVal = col?.sortValue ? col.sortValue(b) : b[sort.key];

                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return sort.dir === 'asc' ? aVal - bVal : bVal - aVal;
                }

                aVal = String(aVal ?? '').toLowerCase();
                bVal = String(bVal ?? '').toLowerCase();
                const cmp = aVal.localeCompare(bVal);
                return sort.dir === 'asc' ? cmp : -cmp;
            });
        }

        return rows;
    }, [data, search, sort, columns]);

    const totalPages = Math.max(1, Math.ceil(processed.length / ROWS_PER_PAGE));
    const pageRows = processed.slice(page * ROWS_PER_PAGE, (page + 1) * ROWS_PER_PAGE);

    return (
        <div className="omsk-datatable">
            <div className="omsk-table-container">
                <table className="omsk-table">
                    <thead>
                        <tr>
                            <th className="omsk-table-rank-col">#</th>
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={`${col.numeric ? 'omsk-table-number' : ''} ${col.sortable !== false ? 'omsk-table-sortable' : ''}`}
                                    onClick={col.sortable !== false ? () => handleSort(col.key) : undefined}
                                >
                                    {col.label}
                                    {col.sortable !== false && sort.key === col.key && (
                                        <span className="omsk-table-sort-icon">
                                            {sort.dir === 'asc' ? ' \u25B2' : ' \u25BC'}
                                        </span>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length + 1} style={{ textAlign: 'center', padding: '20px', color: '#757575' }}>
                                    {search ? __('No matching results', 'openmost-site-kit') : __('No data available', 'openmost-site-kit')}
                                </td>
                            </tr>
                        ) : (
                            pageRows.map((row, idx) => (
                                <tr key={idx}>
                                    <td className="omsk-table-rank-col">
                                        {page * ROWS_PER_PAGE + idx + 1}
                                    </td>
                                    {columns.map((col) => (
                                        <td key={col.key} className={col.numeric ? 'omsk-table-number' : ''}>
                                            {col.render ? col.render(row) : row[col.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div className="omsk-datatable-footer">
                <TextControl
                    placeholder={__('Search...', 'openmost-site-kit')}
                    value={search}
                    onChange={handleSearch}
                    className="omsk-datatable-search"
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />

                {totalPages > 1 && (
                    <div className="omsk-datatable-pagination">
                        <button
                            className="omsk-datatable-page-btn"
                            disabled={page === 0}
                            onClick={() => setPage(0)}
                        >
                            &laquo;
                        </button>
                        <button
                            className="omsk-datatable-page-btn"
                            disabled={page === 0}
                            onClick={() => setPage(page - 1)}
                        >
                            &lsaquo;
                        </button>
                        <span className="omsk-datatable-page-info">
                            {page + 1} / {totalPages}
                        </span>
                        <button
                            className="omsk-datatable-page-btn"
                            disabled={page >= totalPages - 1}
                            onClick={() => setPage(page + 1)}
                        >
                            &rsaquo;
                        </button>
                        <button
                            className="omsk-datatable-page-btn"
                            disabled={page >= totalPages - 1}
                            onClick={() => setPage(totalPages - 1)}
                        >
                            &raquo;
                        </button>
                    </div>
                )}

                <span className="omsk-datatable-count">
                    {processed.length} {processed.length === 1
                        ? __('result', 'openmost-site-kit')
                        : __('results', 'openmost-site-kit')}
                </span>
            </div>
        </div>
    );
};

export default DataTable;
