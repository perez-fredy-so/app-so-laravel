<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MonitorSO extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'so:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorea CPU, Memoria y Disco del Sistema Operativo en tiempo real';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== MONITOREO DE SISTEMA OPERATIVO ===');
        $this->line('Presiona Ctrl+C para detener');
        $this->line('');

        // Crear directorio de logs si no existe
        if (!is_dir(storage_path('logs'))) {
            mkdir(storage_path('logs'), 0755, true);
        }

        $logFile = storage_path('logs/monitor.log');

        // Mensaje inicial en log
        $timestamp = now()->format('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] Iniciando monitoreo...\n", FILE_APPEND);

        // Bucle infinito de monitoreo
        $iterations = 0;
        while (true) {
            $iterations++;

            // Obtener métricas del SO
            $cpuUsage = $this->getCPUUsage();
            $memoryUsage = $this->getMemoryUsage();
            $diskUsage = $this->getDiskUsage();

            // Mostrar en consola
            $this->clearScreen();
            $this->displayMetrics($cpuUsage, $memoryUsage, $diskUsage, $iterations);

            // Guardar en log
            $this->writeLog($logFile, $cpuUsage, $memoryUsage, $diskUsage);

            // Esperar 5 segundos
            sleep(5);
        }

        return 0;
    }

    /**
     * Obtener uso de CPU
     */
    private function getCPUUsage()
    {
        if (php_uname('s') === 'Linux') {
            // En Linux (incluyendo WSL)
            $cpuInfo = shell_exec('top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'');
            return (float) trim($cpuInfo);
        } else {
            // Windows
            return rand(10, 60); // Simulado
        }
    }

    /**
     * Obtener uso de Memoria
     */
    private function getMemoryUsage()
    {
        if (php_uname('s') === 'Linux') {
            // En Linux
            $memoryInfo = shell_exec('free | grep Mem | awk \'{print ($3/$2) * 100.0}\'');
            return (float) trim($memoryInfo);
        } else {
            // Windows
            return rand(30, 70); // Simulado
        }
    }

    /**
     * Obtener uso de Disco
     */
    private function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percentage = ($used / $total) * 100;
        return round($percentage, 2);
    }

    /**
     * Mostrar métricas en consola
     */
    private function displayMetrics($cpu, $memory, $disk, $iteration)
    {
        $this->line('📊 MONITOREO DE SISTEMA - Iteración #' . $iteration);
        $this->line('');

        // CPU
        $this->line('CPU Usage:');
        $this->displayBar('CPU', $cpu, 100);
        $this->line('');

        // Memoria
        $this->line('Memory Usage:');
        $this->displayBar('MEM', $memory, 100);
        $this->line('');

        // Disco
        $this->line('Disk Usage:');
        $this->displayBar('DSK', $disk, 100);
        $this->line('');

        // Timestamp
        $this->info('Actualizado: ' . now()->format('Y-m-d H:i:s'));
        $this->line('Logs guardados en: storage/logs/monitor.log');
    }

    /**
     * Mostrar barra de progreso
     */
    private function displayBar($label, $value, $max)
    {
        $percentage = ($value / $max) * 100;
        $barLength = 30;
        $filledLength = (int) (($value / $max) * $barLength);

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);

        $color = 'info';
        if ($percentage >= 80) {
            $color = 'error';
        } elseif ($percentage >= 60) {
            $color = 'comment';
        }

        $this->line("<$color>$label: [$bar] " . round($percentage, 1) . '%</$color>');
    }

    /**
     * Guardar en archivo de log
     */
    private function writeLog($file, $cpu, $memory, $disk)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $log = "[$timestamp] CPU: " . round($cpu, 2) . "% | MEM: " . round($memory, 2) . "% | DISK: " . round($disk, 2) . "%\n";
        file_put_contents($file, $log, FILE_APPEND);
    }

    /**
     * Limpiar pantalla
     */
    private function clearScreen()
    {
        if (php_uname('s') === 'Linux' || php_uname('s') === 'Darwin') {
            system('clear');
        } else {
            system('cls');
        }
    }
}
