<?php

namespace App\Exports;

use App\Models\TodoLists;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class TodoListsExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    protected $filters;
    protected $totalTimeTracked;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = TodoLists::query();

        // Apply filters
        if (!empty($this->filters['title'])) {
            $query->where('title', 'like', '%' . $this->filters['title'] . '%');
        }

        if (!empty($this->filters['assigne'])) {
            $assignees = explode(',', $this->filters['assigne']);
            $assignees = array_map('trim', $assignees);
            
            $query->where(function($q) use ($assignees) {
              foreach ($assignees as $asg) {
                $q->orWhere('assigne', 'like', "%$asg%");
              }
            });
          }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('due_date', [$this->filters['start_date'], $this->filters['end_date']]);
        }

        if (!empty($this->filters['min_time']) && !empty($this->filters['max_time'])) {
            $query->whereBetween('time_tracked', [$this->filters['min_time'], $this->filters['max_time']]);
        }

        if (!empty($this->filters['status'])) {
            $statuses = explode(',', $this->filters['status']);
            $statuses = array_map('trim', $statuses);
            $query->whereIn('status', $statuses);
        }

        if (!empty($this->filters['priority'])) {
            $priorities = explode(',', $this->filters['priority']);
            $priorities = array_map('trim', $priorities);
            $query->whereIn('priority', $priorities);
        }

        $todos = $query->select('title', 'assigne', 'due_date', 'time_tracked', 'status', 'priority')
                      ->orderBy('due_date', 'asc')
                      ->get();

        // Calculate total time tracked
        $this->totalTimeTracked = $todos->sum('time_tracked');

        return $todos;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Title',
            'Assignee',
            'Due Date',
            'Time Tracked (Hours)',
            'Status',
            'Priority'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->title,
            $row->assigne ?? '-',
            $row->due_date ? $row->due_date->format('Y-m-d') : '-',
            $row->time_tracked ?? 0,
            ucfirst($row->status),
            ucfirst($row->priority)
        ];
    }

    /**
     * @return string
     */
    public function startCell(): string
    {
        return 'A1';
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                
                // Style headers
                $event->sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Add summary row
                $summaryRow = $lastRow + 2;
                $sheet->setCellValue('A' . $summaryRow, 'SUMMARY');
                $sheet->setCellValue('C' . $summaryRow, 'Total Time Tracked:');
                $sheet->setCellValue('D' . $summaryRow, $this->totalTimeTracked . ' hours');

                // Style summary row
                $event->sheet->getStyle('A' . $summaryRow . ':F' . $summaryRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7']
                    ]
                ]);

                // Auto-size columns
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Add borders to all data
                $event->sheet->getStyle('A1:F' . $summaryRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
            }
        ];
    }
}
