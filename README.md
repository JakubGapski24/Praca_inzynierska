# Praca_inzynierska
System przeciwpożarowy ze stroną internetową, która zarządza jego pracą.
System składa się z wyświetlacza LCD i czujników podłączonych do STM32 - czujnik dymu, płomienia, temperatury i switch (jako ROP).
Dane z czujników są wysyłane do bazy danych, na zewnętrzny serwer.
Dostęp do danych można uzyskać poprzez specjalnie stworzony portal.
Ponadto, system posiada moduł GSM, który wysyła powiadomienie SMS, w przypadku alarmowania drugiego stopnia.
Portal składa się z konta Administratora i Operatora.
