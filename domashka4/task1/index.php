<?php

interface ICarsharing
{
    const MINUTES_PER_DAILY = 1440;
    const MINUTES_PER_HOURLY = 60;

    public function calculatePrice();
}

trait GpsTrait
{
    protected function calculateGpsService()
    {
        return ceil($this->minutes / self::MINUTES_PER_HOURLY) * 15;
    }
}

trait DriverTrait
{
    protected function calculateDriverService()
    {
        return 100;
    }
}

abstract class Tariff implements ICarsharing
{
    private $kilometers;
    private $minAge;
    private $age;
    private $isGps;

    protected $pricePerOneKilometers;
    protected $pricePerOneMinutes;
    protected $minutes;
    protected $maxAge;
    protected $isDriver;

    public function __construct($kilometers, $minutes, $age, $gps)
    {
        $this->kilometers = $kilometers;
        $this->minutes = $minutes;
        $this->age = $age;
        $this->minAge = 18;
        $this->maxAge = 65;
        $this->isGps = $gps;
        $this->isDriver = false;
    }

    public function calculatePrice()
    {
        $error = $this->getError();

        if ($error !== false) {
            return $error;
        }

        $totalPrice = $this->calculatePriceForKilometers() + $this->calculatePriceForMinutes();

        if ($this->isGps) {
            $totalPrice += $this->calculateGpsService();
        }

        if ($this->isDriver) {
            $totalPrice += $this->calculateDriverService();
        }

        return $this->insureByAge($totalPrice);
    }

    protected function calculatePriceForKilometers()
    {
        return $this->pricePerOneKilometers * $this->kilometers;
    }

    protected function calculatePriceForMinutes()
    {
        return $this->pricePerOneMinutes * $this->minutes;
    }

    protected function insureByAge($price)
    {
        if ($this->age < 22) {
            $price = $price * 1.1;
        }

        return $price;
    }

    protected function isAvailableAge()
    {
        if ($this->age < $this->minAge || $this->age > $this->maxAge) {
            return false;
        } else {
            return true;
        }
    }

    protected function getError()
    {
        if ($this->kilometers <= 0 && $this->minutes <= 0) {
            return "Нужно указать километраж или количество минут.";
        }

        if (!$this->isAvailableAge()) {
            return "Нельзя использовать текущий план по возрастным ограничениям.";
        }

        return false;
    }
}

class BaseTariff extends Tariff
{
    use GpsTrait, DriverTrait;

    public function __construct($kilometers, $minutes, $age, $gps = false)
    {
        parent::__construct($kilometers, $minutes, $age, $gps);

        $this->pricePerOneKilometers = 10;
        $this->pricePerOneMinutes = 3;
    }
}

class HourlyTariff extends Tariff
{
    use GpsTrait, DriverTrait;

    public function __construct($kilometers, $minutes, $age, $gps = false, $driver = false)
    {
        parent::__construct($kilometers, $minutes, $age, $gps);

        $this->pricePerOneKilometers = 0;
    }

    protected function calculatePriceForMinutes()
    {
        return ceil($this->minutes / self::MINUTES_PER_HOURLY) * 200;
    }
}

class DailyTariff extends Tariff
{
    use GpsTrait, DriverTrait;

    public function __construct($kilometers, $minutes, $age, $gps = false, $driver = false)
    {
        parent::__construct($kilometers, $minutes, $age, $gps);

        $this->pricePerOneKilometers = 1;
    }

    protected function calculatePriceForMinutes()
    {
        $polnihSutok = 1;

        if ($this->minutes > self::MINUTES_PER_DAILY) {
            $polnihSutok = floor($this->minutes / self::MINUTES_PER_DAILY);

            if ($this->minutes - (self::MINUTES_PER_DAILY * $polnihSutok) >= 30) {
                $polnihSutok++;
            }
        }

        return 1000 * $polnihSutok;
    }
}

class StudentsTariff extends Tariff
{
    use GpsTrait, DriverTrait;

    public function __construct($kilometers, $minutes, $age, $gps = false)
    {
        parent::__construct($kilometers, $minutes, $age, $gps);

        $this->pricePerOneKilometers = 4;
        $this->pricePerOneMinutes = 1;
        $this->maxAge = 25;
    }
}

$base = new BaseTariff(10, 65, 21, true);
$hourly = new HourlyTariff(0, 121, 35, true, true);
$daily = new DailyTariff(10, 1470, 32);
$students = new StudentsTariff(10, 10, 21);

$tariffs = [
    $base,
    $hourly,
    $daily,
    $students
];

foreach ($tariffs as $tariff) {
    if ($tariff instanceof ICarsharing) {
        echo $tariff->calculatePrice();
        echo "<br />";
    }
}
