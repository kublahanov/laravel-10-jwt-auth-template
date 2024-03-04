<?php

namespace App\Console\Commands;

use App\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Тест HTTP-клиента.
 */
class TestHttp extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'test:http';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Тест HTTP-клиента';

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $this->newLine();
        $this->alert($this->description);

        $startTime = Carbon::now();

        $response = Http::get('http://example.com');

        $this->line("Статус: {$response->status()}");
        $this->newLine();

        file_put_contents('./resources/views/example.blade.php', $response->body());

        // $this->newLine();
        // $this->alert('alert'); // Жёлтый
        // $this->info('info'); // Зелёный
        // $this->line('line'); // Белый
        // $this->comment('comment'); // Жёлтый
        // $this->question('question'); // Голубой
        // $this->error('error'); // Красный
        // $this->warn('warn'); // Жёлтый

        $duration = (Carbon::now())->diffInSeconds($startTime);
        $this->info("Время выполнения (сек.): {$duration}.");

        return 0;
    }
}
