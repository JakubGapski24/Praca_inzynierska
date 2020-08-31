//************************************************************************* Inicjalizacja bibliotek *************************************************************************************************************
#include <OneWire.h>
#include <DallasTemperature.h> //załączenie bibliotek obsługujących działanie czujnika temperatury
#include <Ethernet.h> //załączenie biblioteki umożliwiającej obsługę połączenia z Internetem poprzez złącze Ethernetowe
#include <LiquidCrystal_I2C.h> //załączenie biblioteki umożliwiającej obsługę wyświetlacza LCD
#include <HardwareSerial.h> //ta biblioteka jest potrzebna do przeysłania danych, potrzebnych do wysłania SMS, ze sterownika do modułu GSM

//************************************************************************* Inicjalizacja niezbędnych zmiennych *************************************************************************************************

LiquidCrystal_I2C lcd(0x27, 20, 4); //inicjalizacja wyświetlacza LCD - 20 znaków w 4 wierszach
OneWire onewire(PB3);
DallasTemperature sensors (&onewire); //inicjalizacja czujnika temperatury - przypisanie wyjścia arduino oraz określenie działania "na jednym przewodzie" 
HardwareSerial serial(PA10, PA9);  //ustawienie komunikacji UART na pinach PA10 - RX oraz PA9 - TX
String tresc_sms; //deklaracja zmiennej, która przechowuje treść wiadomości SMS
char sms[160]; //zmienna z treścią SMS, której typ jest wymagany do prawidłowego przesłania wiadomości

EthernetClient polacz; //utworzenie połączenia przez Ethernet - ta zmiennna służyć będzie do wysyłania danych do bazy danych (za pomocą odpowiedniego skryptu w PHP)
EthernetClient client; //utworzenie połączenia przez Ethernet - ta zmienna służyć będzie do odbierania danych z bazy danych (za pomocą poszczególnych skryptów w PHP)
char inString[32]; //tablica przechowująca "odebrane" dane z odpowiedniego skryptu PHP (przy odbieraniu danych z serwera)
int stringPos = 0; //zmienna określająca pozycje, odbieranych ze skryptu PHP, znaków w tablicy inString (przy odbieraniu danych z serwera)
boolean startRead = false; //zmienna określająca czy nadal odbywa się odczytywanie danych ze skryptu PHP (przy odbieraniu danych z serwera)
IPAddress server_addr(46, 242, 241, 62); //określenie adresu IP serwera, z którym wymieniane będą dane (wysyłanie i odbieranie)

//przypisanie cyfrowych wyjść Arduino dla poszczególnych elementów
#define stan_plomien PC4 //dioda określająca stan czujki płomienia (czy wykryła płomień czy nie)
#define stan_dym PB13 //dioda określająca stan czujki dymu (czy wykryła dym czy nie)
#define rop PC1 //przycisk, który pełni rolę ROP
#define stan_rop PB5 //dioda określająca stan ROP (czy wciśnięty czy nie)
#define potwierdz PB4 //przycisk pełniący funkcję potwierdzania alarmu
#define stan_potwierdz PB10 //dioda określająca czy alarm został potwierdzony czy nie
#define resetuj PA8 //przycisk pełniący funkcję resetowania systemu
#define stan_resetu PC0 //dioda wskazująca stan resetowania systemu
#define flame PC7 //czujnik płomienia
#define alarm1 PA1 //dioda określająca alarm pierwszego stopnia (czy wystąpił czy nie)
#define alarm2 PA4 //dioda określająca alarm drugiego stopnia (czy wystąpił czy nie)
#define stan_temp PB0 //dioda określająca stan czujki temperatury (czy czujka ta spowodowała alarm czy nie)
#define sygnalizator_akustyczny PC10 //sygnalizator dźwiękowy, który załącza się w przypadku wystąpienia pożaru

// inicjalizacja zmiennych dla poszczególnych elementów systemu, które będą zmieniać wartości w konkretnych sytuacjach
char plomien = '0';
char dym = '0';
char ostrzegacz = '0';
char temp = '0'; //pierwsze 4 zmienne określają czy dana czujka wykryła pożar czy nie lub czy jest uszkodzona
float poprzednia_temp = 0; //wartość temperatury zmierzona bezpośrednio przed aktualną wartością (ostatnia zapamiętana wartość temperatury)
float aktualna_temp = 0; //aktualna temperatura zmierzona przez czujnik temperatury
byte licznik_temp = 0; //licznik temperatury, dzięki któremu będzie można stwierdzić czy czujka temperatury ma zgłosić pożar czy nie
bool confirm = false;
bool resecik = false;
bool pozar1 = false;
bool pozar2 = false; //zmienne określające stany odpowiednich stanów systemu - potwierdzenie, reset systemu, alarm pierwszego i drugiego stopnia

byte temperatura_graniczna = 70; //wartość temperatury granicznej, po przekroczeniu której zgłaszany jest alarm - ustawiona na 70 stopni w razie utraty połączenia z internetem
byte potwierdz_resetuj = 0; //zmienna przechowująca dane o tym, czy zresetować system/potwierdzić alarm, czy nie (zmienna ta przechowuje te dane w postaci liczby całkowitej przekonwertowanej
//z typu 'STRING', pobranej ze skryptu PHP
byte potwierdz_lub_resetuj[2]; //inicjalizacja tablicy, która przechowuje dane o potwierdzeniu i resecie - dane te są pobierane z serwera
byte czy_uszkodzone[4]; //inicjalizacja tablicy, która przechowuje dane o tym, czy czujki są uszkodzone - dane te są pobierane z serwera

bool is_online; //zmienna określająca czy sterownik jest połączony z internetem czy nie

unsigned long ustawienia_systemu = 0; //zmienna przechowująca dane o ustawieniach systemu (uszkodzone czujki, czas do alarmu drugiego stopnia) w postaci jednego ciągu znaków - liczby całkowitej
short czas_do_alarmu2 = 20; //czas do alarmu drugiego stopnia (w sekundach) - ustawiony na 20 sekund w razie utraty połączenia z internetem. Dodatkowo w momencie wystąpienia alarmu, liczba ta zmniejsza się co
//1s
short aktualnie_ustawiony_czas_do_alarmu2; //zawiera oryginalny czas do alarmu drugiego stopnia - w momencie "wyjścia" z alarmu pierwszego stopnia, zmiennej "czas_do_alarmu2" może zostać przypisana pierwotna
//wartość

//zmienne służące do odmierzania czasu
unsigned long czasBezAlarmu = 0;
unsigned long zapamietanyCzasBezAlarmu = 0; //zmienne te służą do odmierzania minuty, po upłynięciu której następuje komunikacja z serwerem (informacja o tym, że sterownik jest ONLINE) - tylko, gdy nie ma
//alarmu, potwierdzenia czy resetu systemu - czyli system "czeka" na jakiekolwiek zdarzenie (tryb dozorowania)
unsigned long czasAlarmuBezPotwierdzenia = 0;
unsigned long zapamietanyCzasAlarmuBezPotwierdzenia = 0; //sprawdzanie czy sterownik jest ONLINE w czasie trwania alarmu pierwszego stopnia
unsigned long sprawdzCzyOnline = 0;
unsigned long zapamietanyCzasSprawdzCzyOnline = 0; //zmienne te służą do odmierzania minuty (sprawdzanie czy sterownik jest ONLINE), gdy system wskazuje alarm drugiego stopnia lub potwierdzenie

byte stopnie_celsjusza[8] = {   //symbol stopni (dla potrzeb LCD)
  0b00010,
  0b00101,
  0b00010,
  0b00000,
  0b00000,
  0b00000,
  0b00000,
  0b00000
};
byte N[8] = {   //litera "ń" (dla potrzeb LCD)
  0b00010,
  0b00100,
  0b10110,
  0b11001,
  0b10001,
  0b10001,
  0b10001,
  0b00000
};
byte ZY[8] = {   //litera "ż" (dla potrzeb LCD)
  0b00010,
  0b01111,
  0b00001,
  0b00010,
  0b00100,
  0b01000,
  0b01111,
  0b00000
};
byte ZI[8] = {   //litera "ź" (dla potrzeb LCD)
  0b00010,
  0b00100,
  0b01110,
  0b00010,
  0b00100,
  0b01000,
  0b01110,
  0b00000
};

//*********************************************************************************** Ustawienia początkowe systemu *********************************************************************************************
void setup() {
  //ustawienie podłączonych elementów jako wyjścia (czujniki i diody) oraz wejścia (przyciski)
  pinMode(rop, INPUT_PULLUP); //przycisk ROP jako wejście
  pinMode(stan_rop, OUTPUT); //dioda sygnalizująca, czy ROP wywołał alarm czy nie (jako wyjście)
  pinMode(stan_temp, OUTPUT); //dioda sygnalizująca, czy czujnik temperatury wywołał alarm czy nie (jako wyjście)
  pinMode(stan_plomien, OUTPUT); //dioda sygnalizująca, czy czujnik płomienia wywołał alarm czy nie (jako wyjście)
  pinMode(stan_dym, OUTPUT); //dioda sygnalizująca, czy czujnik dymu wywołał alarm czy nie (jako wyjście)
  pinMode(potwierdz, INPUT_PULLUP); //przycisk potwierdzający alarm
  pinMode(stan_potwierdz, OUTPUT); //dioda sygnalizująca, czy alarm został potwierdzony czy nie
  pinMode(resetuj, INPUT_PULLUP); //przycisk resetujący system
  pinMode(stan_resetu, OUTPUT); //dioda sygnalizująca, czy system jest resetowany czy nie
  pinMode(flame, INPUT); //czujnik płomienia (jako wejście)
  pinMode(alarm1, OUTPUT); //dioda sygnalizująca alarm pierwszego stopnia
  pinMode(alarm2, OUTPUT); //dioda sygnalizująca alarm drugiego stopnia
  pinMode(sygnalizator_akustyczny, OUTPUT); //sygnalizator dźwiękowy
  //zgaszenie wszystkich diod
  digitalWrite(stan_rop, LOW);
  digitalWrite(stan_temp, LOW);
  digitalWrite(stan_plomien, LOW);
  digitalWrite(stan_dym, LOW);
  digitalWrite(stan_potwierdz, LOW);
  digitalWrite(stan_resetu, LOW);
  digitalWrite(alarm1, LOW);
  digitalWrite(alarm2, LOW);
  //wyłączenie sygnalizatora
  digitalWrite(sygnalizator_akustyczny, LOW);
  //inicjalizacja pracy czujnika temperatury
  sensors.begin();
  //inicjalizacja modułu GSM
  //sim.begin();
  //inicjalizacja pracy wyświeltacza LCD
  lcd.init();
  lcd.clear(); //wyczyszczenie ekranu
  lcd.backlight(); //włączenie podświetlenia LCD
  //inicjalizacja, utworzenie znaków specjalnych - liter 'ń', 'ź', 'ż' oraz symbolu stopni Celsjusza
  lcd.createChar(0, stopnie_celsjusza);
  lcd.createChar(1, N);
  lcd.createChar(2, ZY);
  lcd.createChar(3, ZI);
  byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xEE }; // przypisanie unikatowego adresu MAC
  // inicjalizacja połączenia przez Ethernet
  if (Ethernet.begin(mac) != 0) { //jeżeli adres MAC jest prawidłowy i na 100% unikatowy, to wstaw pierwsze zmonitorowane dane do bazy danych
    sensors.requestTemperatures(); //zmierz temperaturę
    aktualna_temp = sensors.getTempCByIndex(0); //przypisz jej wartość do zmiennej 'aktualna_temp' (innymi słowy, zapamiętaj tę wartość)
    StringToInt_pobierz_ustawienia_systemu(false); //pobierz dane (czas do alarmu drugiego stopnia, informacje o uszkodzonych czujkach i zapamiętaj te dane)
    StringToInt_pobierz_ustawienia_systemu(true); //pobierz dane o temperaturze granicznej
    //tylko w tym miejscu programu pobierane są wszystkie dane jednocześnie - 'true' oznacza, że system jest po zresetowaniu i tylko wtedy pobiera aktualną wartość temperatury granicznej
    CZUJNIKI(); //monitoruj stany czujników (w tym wypadku, ustaw odpowiednie stany, zgodnie z otrzymanymi danymi o uszkodzonych czujnikach)
    wstaw(false); //wstaw dane do bazy danych ('false' oznacza, że dodany będzie nowy rekord ze wszystkimi koniecznymi informacjami)
  }
}

//************************************************************************************* Program właściwy ********************************************************************************************************
void loop() {
  StringToInt_pobierz_ustawienia_systemu(false); //pobierz dane (w tym wypadku - czas do alarmu drugiego stopnia, informacje o uszkodzonych czujkach i zapamiętaj te dane)
  czasBezAlarmu = millis();
  czasAlarmuBezPotwierdzenia = millis(); //zacznij odmierzać wyżej nazwane czasy
  CZUJNIKI(); //sprawdź, czy któryś z czujników nie wskazuje alarmu

  if (ostrzegacz != '1' && temp != '1' && plomien != '1' && dym != '1' && !pozar1 && !pozar2) //jeżeli w danym momencie nie ma żadnego alarmu i żaden z czujników go nie wskazuje, to...
  {
    zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia; //nadpisz zmienną, która służy do odmierzania czasu w czasie alarmu pierwszego stopnia
    if (czasBezAlarmu - zapamietanyCzasBezAlarmu >= 60000UL) // jeżeli system nie wykrył pożaru i minęło 60s, to prześlij dane do bazy danych
    {
      zapamietanyCzasBezAlarmu = czasBezAlarmu; //nadpisz zmienną, która służy do odmierzania czasu, gdy nie ma żadnego alarmu
      wstaw(true); //prześlij dane do bazy danych (w tym wypadku, zaktualizuj tylko datę ostatniego połączenia sterownika z serwerem)
    }
  }
}
//*********************************************************************************** Monitorowanie czujników ***************************************************************************************************
void CZUJNIKI() {

  //********************************************************************************* CZUJNIK PŁOMIENIA *********************************************************************************************************

  if (digitalRead(stan_plomien) == LOW) { //jeżeli czujnik płomienia nie wywołał alarmu pierwszego stopnia (dioda odpowiedzialna za stan czujnika płomienia nie świeci się), to...
    lcd.setCursor(0, 0); //ustaw kursor w lewym górnym rogu wyświetlacza LCD
    if (czy_uszkodzone[0] == 0) { //jeżeli czujnik płomienia nie jest oznaczony jako uszkodzony, to...
      if (digitalRead(flame) == HIGH) { //...sprawdź czy wykrywa w danej chwili płomień...
        plomien = '1'; //jeśli tak, to wstaw odpowiednie dane do bazy danych (w tym wypadku wstaw nowy rekord, który wskazuje, że alarm pierwszego stopnia wywołał czujnik płomienia)
        pozar1 = true;
        wstaw(false);
        digitalWrite(stan_plomien, HIGH); //zapal diodę sygnalizującą, że czujnik płomienia uruchomił alarm pierwszego stopnia
        if (digitalRead(alarm1) == LOW) { //jeżeli alarm pierwszego stopnia nie został jeszcze w ogóle wywołany, to wejdź w alarmowanie pierwszego stopnia
          ALARM_PIERWSZEGO_STOPNIA();
        }
      }
      else if (plomien != '1' && !pozar1) { //jeżeli czujnik płomienia nie wykrył w danej chwili płomienia, to sprawdź czy nie wskazuje już on alarmu i czy sam alarm już nie występuje
        plomien = '0'; //jeśli powyższy warunek jest spełniony, to znaczy, że czujnik płomienia nie wykrył płomienia, więc można ustawić jego stan jako "nie wykryto"
        lcd.print(F("Ogie")); //wyświetl tą informację na wyświetlaczu LCD
        lcd.print((char)1); //wyświetlenie litery 'ń'
        lcd.print(F(": NIE WYKRYTO  "));
      }
    }
    else if (czy_uszkodzone[0] == 1  && !pozar1) { //jeżeli czujnik płomienia jest uszkodzony i w danej chwili nie ma alarmu pierwszego stopnia
      plomien = '-'; //to ustaw czujnik płomienia jako uszkodzony
      lcd.print(F("Ogie")); //i wyświetl tą informację na wyświetlaczu na LCD
      lcd.print((char)1); //wyświetlenie litery 'ń'
      lcd.print(F(": USZKODZONY   "));
    }
  }
  //********************************************************************************** CZUJNIK DYMU *************************************************************************************************************

  if (digitalRead(stan_dym) == LOW) { //jeżeli czujnik dymu nie wywołał alarmu pierwszego stopnia (dioda odpowiedzialna za stan czujnika dymu nie świeci się), to...
    lcd.setCursor(0, 1); //ustaw kursor na samym początku drugiej linii wyświetlacza LCD
    if (czy_uszkodzone[1] == 0) { //jeżeli czujnik dymu nie jest uszkodzony, to...
      short procent_dym = map(analogRead(PA0), 0, 1023, 0, 100); //przelicz otrzymane napięcie z zakresu (0-5V) na procenty

      if (procent_dym >= 30) { //jeżeli natężenie dymu osiągnęło 30% lub więcej, to wstaw odpowiednie dane do bazy danych (w tym wypadku wstaw nowy rekord, który wskazuje, że alarm pierwszego stopnia wywołał
        //czujnik dymu
        dym = '1';
        pozar1 = true;
        wstaw(false);
        digitalWrite(stan_dym, HIGH); //zapal diodę sygnalizującą, że czujnik dymu uruchomił alarm pierwszego stopnia
        if (digitalRead(alarm1) == LOW) { //jeżeli alarm pierwszego stopnia nie został jeszcze w ogóle wywołany, to wejdź w alarmowanie pierwszego stopnia
          ALARM_PIERWSZEGO_STOPNIA();
        }
      }
      else if (dym != '1' && !pozar1) { //jeżeli czujnik dymu nie wykrył w danej chwili dymu, to sprawdź czy nie wskazuje już on alarmu i czy sam alarm już nie występuje
        dym = '0'; //jeżeli powyższy warunek jest spełniony, to znaczy, że czujnik dymu nie wykrył dymu, więc można ustawić jego stan jako "nie wykryto"
        lcd.print(F("Dym: NIE WYKRYTO    ")); //i wyświetlić tą informację na wyświetlaczu LCD
      }
    }
    else if (czy_uszkodzone[1] == 1 && !pozar1) { //jeżeli czujnik dymu jest uszkodzony i w danej chwili nie ma alarmu pierwszego stopnia
      dym = '-'; //to ustaw czujnik dymu jako uszkodzony
      lcd.print(F("Dym: USZKODZONY     ")); //i wyświetl tą informację na wyświetlaczu LCD
    }
  }
  //************************************************************************************* ROP *******************************************************************************************************************

  if (digitalRead(stan_rop) == LOW) { //jeżeli ROP nie wywołał alarmu pierwszego stopnia (dioda odpowiedzialna za stan ROP nie świeci się), to...
    lcd.setCursor(0, 2); //ustaw kursor na samym początku trzeciej linii wyświetlacza LCD
    if (czy_uszkodzone[2] == 0) { //jeżeli ROP nie jest uszkodzony, to...

      if (digitalRead(rop) == LOW) // ...sprawdź, czy jest on w danej chwili wciśnięty
      {
        ostrzegacz = '1'; // jeżeli powyższy warunek jest spełniony, to wstaw odpowiednie dane do bazy danych (w tym wypadku wstaw nowy rekord, który wskazuje, że alarm pierwszego stopnia wywołał ROP
        pozar1 = true;
        wstaw(false);
        digitalWrite(stan_rop, HIGH); // zapal diodę sygnalizującą, że ROP uruchomił alarm pierwszego stopnia
        if (digitalRead(alarm1) == LOW) { //jeżeli alarm pierwszego stopnia nie został jeszcze w ogóle wywołany, to wejdź w alarmowanie pierwszego stopnia
          ALARM_PIERWSZEGO_STOPNIA();
        }
      }
      else if (ostrzegacz != '1' && !pozar1) { //jeżeli ROP nie jest wciśnięty, to sprawdź czy nie wskazuje on już alarmu i czy sam alarm już nie występuje
        ostrzegacz = '0'; //jeżeli powyższy warunek jest spełniony, to znaczy, że ROP nie jest wciśnięty, więc można ustawić jego stan jako "nie wykryto"
        lcd.print(F("ROP: NIE WYKRYTO    ")); //i wyświetlić tą informację na wyświetlaczu LCD
      }
    }
    else if (czy_uszkodzone[2] == 1 && !pozar1) { //jeżeli ROP jest uszkodzony i w danej chwili nie ma alarmu pierwszego stopnia
      ostrzegacz = '-'; //to ustaw ROP jako uszkodzony
      lcd.print(F("ROP: USZKODZONY     ")); //i wyświetl tą informację na wyświetlaczu LCD
    }
  }
  //******************************************************************************* CZUJNIK TEMPERATURY *********************************************************************************************************

  if (digitalRead(stan_temp) == LOW) { //jeżeli czujnik temperatury nie wywołał alarmu pierwszego stopnia (dioda odpowiedzialna za stan czujnika temperatury nie świeci się), to...
    lcd.setCursor(0, 3); //ustaw kursor na samym początku czwartej linii wyświetlacza LCD
    if (czy_uszkodzone[3] == 0) { //jeżeli czujnik temperatury nie jest uszkodzony, to...
      sensors.requestTemperatures();
      aktualna_temp = sensors.getTempCByIndex(0); //zmierz aktualną temperaturę otoczenia
      if (aktualna_temp - poprzednia_temp >= 0.2 && poprzednia_temp != 0) { //jeżeli różnica między aktualną temperaturą, a poprzednią jest większa lub równa 0.2 stopnia, gdzie poprzedni pomiar różny od
        //stanu początkowego, to...
        licznik_temp++; //zwiększ licznik pomiarów spełniających ten warunek o jeden
        wstaw(true); //i wstaw odpowiednie dane do bazy danych (w tym przypadku nie wstawiaj nowego rekordu, tylko aktualizuj wartość temperatury i czas ostatniego połączenia się sterownika z serwerem)
      }
      else { //jeżeli powyższy warunek nie jest spełniony, to wyzeruj licznik
        licznik_temp = 0;
      }

      poprzednia_temp = aktualna_temp; //zapamiętaj aktualną wartość temperatury otoczenia

      if ((licznik_temp >= 5 || aktualna_temp >= temperatura_graniczna)) { //jeżeli nastąpi minimum 5 przypadków zwiększenia temperatury o minimum 0.2 stopnia lub aktualna wartość temperatury jest większa od
        //wartości temperatury granicznej, to...
        temp = '1'; //wstaw odpowiednie dane do bazy danych (w tym wypadku wstaw nowy rekord, który wskazuje, że alarm pierwszego stopnia wywołał czujnik temperatury)
        pozar1 = true;
        wstaw(false);
        digitalWrite(stan_temp, HIGH); //zapal diodę sygnalizującą, że czujnik temperatury uruchomił alarm pierwszego stopnia
        if (digitalRead(alarm1) == LOW) { //jeżeli alarm pierwszego stopnia nie został jeszcze w ogóle wywołany, to wejdź w alarmowanie pierwszego stopnia
          ALARM_PIERWSZEGO_STOPNIA();
        }
      }
      else if (temp != '1' && !pozar1) { //jeżeli czujnik temperatury nie wskazał alarmu (zgodnie z poprzednim warunkiem), to sprawdź czy już go nie wskazał i czy sam alarm już nie występuje
        temp = '0'; //jeżeli powyższy warunek jest spełniony, to znaczy, że czujnik temperatury nie wskazał alarmu pierwszego stopnia, więc można ustawić jego stan jako "nie wykryto"
        lcd.print(F("Temp.: ")); //i wyświetlić aktualną temperaturę wraz z licznikiem na wyświetlaczu LCD
        lcd.print(aktualna_temp);
        lcd.print((char)0); //wyświetlenie symbolu stopni
        lcd.print(F("C"));
        lcd.print(F(" ("));
        lcd.print(licznik_temp);
        lcd.print(F(")  "));
      }
    }
    else if (czy_uszkodzone[3] == 1  && !pozar1) { //jeżeli czujnik temperatury jest uszkodzony i w danej chwili nie ma alarmu pierwszego stopnia
      temp = '-'; //to ustaw czujnik temperatury jako uszkodzony
      lcd.print(F("Temp.: USZKODZONY   ")); //i wyświetl tą informację na wyświetlaczu LCD
    }
  }
}

//************************************************************************************* Alarm pierwszego stopnia ************************************************************************************************
void ALARM_PIERWSZEGO_STOPNIA() {
  StringToInt_pobierz_ustawienia_systemu(false); //pobierz dane (czas do alarmu drugiego stopnia, informacje o uszkodzonych czujkach i zapamiętaj te dane)
  digitalWrite(alarm1, HIGH); //zapal diodę sygnalizującą alarm pierwszego stopnia
  digitalWrite(alarm2, LOW); //zgaś diodę sygnalizującą alarm drugiego stopnia
  digitalWrite(sygnalizator_akustyczny, HIGH);
  byte licznik = 0; //inicjalizacja zmiennej potrzebnej do odmierzania czasu w trakcie trwania alarmu pierwszego stopnia
  int minuty = czas_do_alarmu2 / 60; //oblicz liczbę minut z aktualnie ustawionego czasu do wystąpienia alarmu drugiego stopnia (czas ten jest oryginalnie zapisany w sekundach)
  int sekundy = (czas_do_alarmu2 % 60) + 1; //po obliczeniu liczby minut, zapisz pozostałe sekundy i dodaj 1s (w celu prawidłowego wyświetlania odliczanego czasu na LCD)

  //wyświetlenie informacji o pożarze pierwszego stopnia
  lcd.clear(); //czyść ekran
  lcd.setCursor(1, 0); //ustaw kursor lewym górnym rogu wyświetlacza
  lcd.print(F("PO"));
  lcd.print((char)2); //wyświetl literę 'ż'
  lcd.print(F("AR 1-GO STOPNIA!")); //pokaż komunikat
  lcd.setCursor(7, 1); //ustaw kursor na ósmej pozycji drugiej linii (pierwsza pozycja ma numer 0)
  lcd.print(F("Czas:")); //pokaż dalszą część komunikatu
  lcd.setCursor(0, 3); //ustaw kursor na początku ostatniej linii
  lcd.print(F("Potwierd")); //pokaż dalszą część komunikatu
  lcd.print((char)3); //wyświetl literę 'ź'
  lcd.print(F("    Resetuj")); //pokaż ostatnią część komunikatu
  zapamietanyCzasSprawdzCzyOnline = 0; //zapis ten jest potrzebny, by prawidłowo działało odmierzanie czasu w trakcie alarmowania drugiego stopnia

  while (pozar1) { //dopóki trwa alarm pierwszego stopnia
    czasAlarmuBezPotwierdzenia = millis(); //zacznij odmierzać czas, dzięki któremu będzie można sprawdzać czy sterownik jest online
    if (czasAlarmuBezPotwierdzenia - zapamietanyCzasAlarmuBezPotwierdzenia >= 1000UL) { //jeżeli minęła 1s, to...
      licznik++; //zwiększ 'licznik sekund' o 1
      if (licznik == 60) { //jeżeli ten warunek jest spełniony, to znaczy, że minęła 1 minuta i...
        wstaw(true); //wstaw dane do bazy danych (w tym wypadku zaktualizuj tylko aktualną temperaturę i datę ostatniego łączenia z bazą danych)
        licznik = 0; //wyzeruj licznik, by odmierzać kolejną minutę
      }
      zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia; //zapamiętaj "stary" czas, by można było odmierzyć kolejną sekundę
      StringToInt_pobierz_ustawienia_systemu(false); //w tym wypadku, pobierz tylko dane o resecie/potwierdzeniu, by sprawdzać czy takie polecenie nie przyszło z poziomu strony
      czasBezAlarmu = millis();
      zapamietanyCzasBezAlarmu = czasBezAlarmu; //te dwie linijki są konieczne do prawidłowego odmierzania czasu i nie wstawiania zbyt często danych do bazy, w momencie gdy system jest w stanie dozorowania
      sekundy--; //zmniejszaj liczbę sekund o 1, by prawidłowo wyświetlać odmierzanie czasu na LCD (dzięki temu, możliwe jest wyświetlenie również '00:00')
      lcd.setCursor(7, 2); //ustaw kursor na ósmej pozycji trzeciej linii (w tej linii będzie wyświetlany aktualny czas do alarmu drugiego stopnia)
      //określenie, jak będą wyświetlane minuty
      if (minuty >= 10) {
        lcd.print(minuty);
        lcd.print(F(":"));
      }
      else if (minuty < 10 && minuty >= 1) {
        lcd.print(F("0"));
        lcd.print(minuty);
        lcd.print(F(":"));
      }
      else if (minuty == 0) {
        lcd.print(F("00:"));
      }
      //określenie, jak będą wyświetlane sekundy
      if (sekundy >= 10) {
        lcd.print(sekundy);
      }
      else if (sekundy < 10) {
        lcd.print(F("0"));
        lcd.print(sekundy);
      }

      //jeżeli ten warunek jest spełniony, to trzeba zmniejszyć liczbę minut o 1 i ustawić sekundy na 60, by po zmniejszeniu tej liczby o 1 mogło zostać prawidłowo wyświetlone 59
      if (minuty != 0 && sekundy == 0) {
        minuty--;
        sekundy = 60;
      }
      czas_do_alarmu2 = (minuty * 60) + sekundy; //czas, jaki jeszcze pozostał do alarmu drugiego stopnia - gdy kolejna czujka wskaże alarm, zmienna ta jest "wrzucana" w odpowiednie miejsce bazy danych
      //dzięki temu na stronie (która zostanie odświeżona), będzie nadal wyświetlała prawidłowy czas do alarmu drugiego stopnia
      CZUJNIKI(); //monitoruj pozostałe czujniki, czy nie wykryły alarmu
      if (POTWIERDZ()) { //sprawdź, czy nie wciśnięto przycisku 'potwierdź' lub takie polecenie nie przyszło z poziomu strony
        break; //wyjdź z alarmowania pierwszego stopnia
      }
      if (RESETUJ()) { //sprawdź,nie wciśnięto przycisku 'reset' lub takie polecenie nie przyszło z poziomu strony
        break; //wyjdź z alarmowania pierwszego stopnia
      }

      if (minuty == 0 && sekundy == 0) { //jeżeli odliczanie czasu dobiegnie końca, to wejdź w alarmowanie drugiego stopnia
        ALARM_DRUGIEGO_STOPNIA();
      }
    }
  }
}

//******************************************************************************************* Alarm drugiego stopnia ********************************************************************************************
void ALARM_DRUGIEGO_STOPNIA()
{
  //ustawienie odpowiednich danych, które zostają wysłane do bazy danych - teraz system jest w trybie alarmowania drugiego stopnia
  resecik = false;
  confirm = false;
  pozar1 = false;
  pozar2 = true;
  digitalWrite(alarm1, LOW); //zgaś diodę sygnalizującą alarm pierwszego stopnia
  digitalWrite(alarm2, HIGH); //zapal diodę sygnalizującą alarm drugiego stopnia
  wstaw(false); //wstaw do bazy danych nowy rekord, który uwzględnia przejście systemu w alarmowanie drugiego stopnia
  //pokaż odpowiedni komunikat na LCD
  lcd.clear(); //wyczyść ekran
  lcd.setCursor(6, 0);
  lcd.print(F("UWAGA!!!"));
  lcd.setCursor(1, 1);
  lcd.print(("PO"));
  lcd.print((char)2); //wyświetl literę 'ż'
  lcd.print(F("AR 2-GO STOPNIA!"));
  lcd.setCursor(0, 3);
  lcd.print(F("Potwierd"));
  lcd.print((char)3); //wyświetl literę 'ź'
  lcd.print(F("    Resetuj"));

  //określenie treści wysyłanej wiadomości SMS
  tresc_sms = "UWAGA!!!\nWykryto alarm pozarowy drugiego stopnia!\nUrzadzenia, ktore wskazaly zagrozenie:\n"; //treść wiadomości SMS
  //dostosuj treść SMS, w zależności od tego, który czujnik wskazał alarm
  if (plomien == '1') {
    tresc_sms += "Czujnik plomienia\n";
  }
  if (dym == '1') {
    tresc_sms += "Czujnik dymu\n";
  }
  if (ostrzegacz == '1') {
    tresc_sms += "ROP\n";
  }
  if (temp == '1') {
    tresc_sms += "Czujnik temperatury (" + String(aktualna_temp,2) + " C)";
  }
  tresc_sms.toCharArray(sms,160); //konwersja typów, w celu prawidłowego wysłania SMS

  serial.begin(9600); //inicjalizacja UART
  delay(100);
  serial.write("AT+CMGF=1\r\n"); //ustawienie SMS w tryb tekstowy - rozpoczęcie procedury wysłania SMS
  delay(100);
  serial.write("AT+CMGS=\"+48604397034\"\r\n"); //określenie numeru telefonu, pod który wysłany zostanie SMS
  delay(100);
  serial.write(sms); //wysłanie SMS
  delay(100);
  serial.write((char)26); //zakończenie wysyłania SMS
  delay(100); 
  
  bool started = false; //ustawienie zmiennej pomocniczej przy odmierzaniu czasu

  while (digitalRead(alarm2) == HIGH) // dopóki nie potwierdzisz alarmu lub nie zresetujesz systemu, to pozostań w alarmowaniu drugiego stopnia
  {
    sprawdzCzyOnline = millis(); //zacznij odmierzać czas
    if (!started) { //tylko w tym przypadku nadpisz czas - jest to konieczne, aby wstawienie aktualnych danych do bazy nie nastąpiło zbyt szybko
      zapamietanyCzasSprawdzCzyOnline = sprawdzCzyOnline;
      started = true;
    }
    if (sprawdzCzyOnline - zapamietanyCzasSprawdzCzyOnline >= 60000UL) { //jeżeli minęła 1 minuta (60s), to...
      zapamietanyCzasSprawdzCzyOnline = sprawdzCzyOnline; //odmierzaj kolejne 60s
      wstaw(true); //wstaw odpowiednie dane do bazy danych - w tym wypadku aktualizuj temperaturę i czas ostatniego połączenia z serwerem
    }
    //nadpisuj zmienne służące do mierzenia czasu w trybie alarmowania pierwszego stopnia i gdy nie ma alarmu - gdy system przejdzie później do tych trybów, pozwoli to na wstawianie danych do bazy
    //w odpowiednim czasie
    czasBezAlarmu = millis();
    zapamietanyCzasBezAlarmu = czasBezAlarmu;
    zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia;
    StringToInt_pobierz_ustawienia_systemu(false); //pobierz tylko dane o resecie/potwierdzeniu z poziomu strony
    if (POTWIERDZ()) { //sprawdź, czy nie ma polecenia o potwierdzeniu alarmu
      break; //wyjdź z alarmowania drugiego stopnia
    }
    if (RESETUJ()) { //sprawdź, czy nie ma polecenia o resecie systemu
      break; //wyjdź z alarmowania drugiego stopnia
    }
  }
}

//********************************************************************************************* Potwierdzenie alarmu ********************************************************************************************
bool POTWIERDZ() {
  byte licznik = 0; //ustawienie zmiennej pomocniczej przy odmierzaniu czasu
  if (digitalRead(potwierdz) == LOW && digitalRead(stan_potwierdz) == LOW) // jeśli dioda sygnalizująca potwierdzenie alarmu się nie świeci i wciśnięto przycisk o potwierdzeniu alarmu, to...
  {
    wlacz_POTWIERDZ(false); //wejdź w tryb potwierdzenia - 'false' oznacza, że nie jest to polecenie z poziomu strony
  }
  else if (potwierdz_lub_resetuj[0] == 1 && digitalRead(stan_potwierdz) == LOW) { //jeżeli dioda sygnalizująca potwierdzenie alarmu się nie świeci i otrzymano polecenie potwierdzenia z poziomu strony, to...
    wlacz_POTWIERDZ(true); //wejdź w tryb potwierdzenia - 'true' oznacza, że jest to polecenie z poziomu strony
  }

  while (digitalRead(stan_potwierdz) == HIGH) { //dopóki system znajduje się w trybie potwierdzenia alarmu - na co wskazuje zapalona odpowiednia dioda, to...
    sprawdzCzyOnline = millis(); //zacznij odmierzać czas
    if (licznik == 0) { //tylko w tym przypadku nadpisz czas - jest to konieczne, aby wstawienie aktualnych danych do bazy nie nastąpiło zbyt szybko
      zapamietanyCzasSprawdzCzyOnline = sprawdzCzyOnline;
      licznik++;
    }
    if (sprawdzCzyOnline - zapamietanyCzasSprawdzCzyOnline >= 60000UL) { //jeżeli minęła 1 minuta (60s), to...
      zapamietanyCzasSprawdzCzyOnline = sprawdzCzyOnline; //odmierzaj kolejne 60s
      wstaw(true); //wstaw odpowiednie dane do bazy danych - w tym wypadku aktualizuj temperaturę i czas ostatniego połączenia z serwerem
    }
    StringToInt_pobierz_ustawienia_systemu(false); //pobierz tylko dane o resecie/potwierdzeniu z poziomu strony
    //nadpisuj zmienne służące do mierzenia czasu w trybie alarmowania pierwszego stopnia i gdy nie ma alarmu - gdy system przejdzie później do tych trybów, pozwoli to na wstawianie danych do bazy
    //w odpowiednim czasie
    czasBezAlarmu = millis();
    zapamietanyCzasBezAlarmu = czasBezAlarmu;
    zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia;
    //wyświetl informację o potwierdzeniu alarmu
    lcd.setCursor(1, 0);
    lcd.print("Alarm potwierdzony");
    lcd.setCursor(0, 3);
    lcd.print("Kasuj stany  Resetuj");
    if (digitalRead(potwierdz) == LOW) { //jeżeli wciśnięto przycisk potwierdzenia, to...
      wylacz_POTWIERDZ(false); //wyłącz potwierdzenie - 'false' oznacza, że polecenie to nie przyszło ze strony
      return true; //wyjdź z trybu potwierdzenia alarmu
    }
    else if (potwierdz_lub_resetuj[0] == 0 && is_online && ustawienia_systemu != 0) { //jeżeli otrzymano polecenie ze strony o wyłączeniu potwierdzenia - drugi i trzeci warunek, jeśli prawdziwe, świadczą o
    //tym, że faktycznie otrzymano takie polecenie (gdy sterownik utraci połączenie z serwerem, to pierwszy warunek jest zawsze spełniony, co nie świadczy o faktycznym otrzymaniu takiego polecenia ze strony)
      wylacz_POTWIERDZ(true); //wyłącz potwierdzenie - 'true' oznacza, że polecenie to otrzymano z poziomu strony
      return true; //wyjdź z trybu potwierdzenia alarmu
    }
    if (RESETUJ()) { //sprawdź, czy nie ma polecenia o resecie systemu
      return true; //wyjdź z trybu potwierdzenia alarmu
    }
  }
  return false; //wróć do funkcji wywołującej w danej chwili funkcję 'POTWIERDZ'
}

void wlacz_POTWIERDZ(bool czy_polecenie_ze_strony) {
  lcd.clear(); //wyczyść ekran LCD
  digitalWrite(stan_potwierdz, HIGH); // zapal diodę sygnalizującą stan potwierdzenia alarmu
  //ustaw odpowiednie informacje 
  resecik = false;
  confirm = true;
  pozar1 = false;
  pozar2 = false;
  //wyłącz alarmowanie
  digitalWrite(alarm1, LOW);
  digitalWrite(alarm2, LOW);
  digitalWrite(sygnalizator_akustyczny, LOW);
  if (!czy_polecenie_ze_strony) { //jeżeli potwierdzenie nie było poleceniem z poziomu strony, to wstaw odpowiednie dane do bazy danych (jeśli polecenie jest z poziomu strony, to dane zostaną wstawione z jej
  //poziomu
    wstaw(false);
  }
}

void wylacz_POTWIERDZ(bool czy_polecenie_ze_strony) {
  lcd.clear(); //wyczyść ekran LCD
  //wyłącz diody sygnalizujące potwierdzenie alarmu oraz sygnalizujące poszczególne czujniki
  digitalWrite(stan_potwierdz, LOW);
  digitalWrite(stan_rop, LOW);
  digitalWrite(stan_plomien, LOW);
  digitalWrite(stan_dym, LOW);
  digitalWrite(stan_temp, LOW);
  
  //jeżeli któryś czujnik wskazywał alarm, to ustaw go w tryb dozorowania (jeśli jest ustawiony jako uszkodzony, to nie zmieniaj jego stanu - informacja o uszkodzeniu jest ustawiana tylko na poziomie strony)
  if (plomien == '1') {
    plomien = '0';
  }
  if (dym == '1') {
    dym = '0';
  }
  if (ostrzegacz == '1') {
    ostrzegacz = '0';
  }
  if (temp == '1') {
    temp = '0';
  }
  //wyzeruj wszystkie zmienne niezbędne do pracy systemu
  poprzednia_temp = 0;
  licznik_temp = 0;
  confirm = false;
  resecik = false;
  //nadpisanie tych zmiennych jest niezbędne do późniejszego, prawidłowego odmierzania czasu w poszczególnych fragmentach programu
  zapamietanyCzasBezAlarmu = czasBezAlarmu;
  zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia;
  //wyświetl komunikat o wyjściu z trybu potwierdzenia (wyjście z tego trybu jest swego rodzaju mini-resetem)
  lcd.clear();
  lcd.setCursor(1, 0);
  lcd.print(F("Czujki zresetowane"));
  if (!czy_polecenie_ze_strony) { //jeżeli wyłączenie potwierdzenia nie było poleceniem z poziomu strony, to wstaw odpowiednie dane do bazy danych
    //(jeśli wyłączenie potwierdzenia było poleceniem ze strony, to dane zostaną wstawione z jej poziomu
    wstaw(false);
  }
  delay(1000); //wstrzymaj pracę całego systemu na 1s - w celu stabilizacji pracy całego systemu
}

//******************************************************************************************** Resetowanie systemu **********************************************************************************************
bool RESETUJ() {
  if (digitalRead(resetuj) == LOW) //jeżeli wciśnięto przycisk resetowania, to...
  {
    wlacz_RESET(6, false); //wejdź w tryb resetowania systemu ('6' oznacza czas do zresetowania w sekundach (czas ten będzie natychmiast zmniejszony o 1s na początku wywołanej funkcji - 
    //w celu prawidłowego wyświetlania na LCD); 'false' oznacza, że polecenie o zresetowaniu systemu nie przyszło z poziomu strony)
    return true; //wyjdź z trybu resetowania
  }
  else if (potwierdz_lub_resetuj[1] == 1) {
    wlacz_RESET(6, true); //wejdź w tryb resetowania systemu ('6' oznacza czas do zresetowania w sekundach (czas ten będzie natychmiast zmniejszony o 1s na początku wywołanej funkcji - 
    //w celu prawidłowego wyświetlania na LCD); 'true' oznacza, że polecenie o zresetowaniu systemu przyszło z poziomu strony)
    return true; //wyjdź z trybu resetowania
  }
  return false; //wróć do funkcji wywołującej w danej chwili funkjcę 'RESETUJ'
}

void wlacz_RESET(byte czas, bool czy_polecenie_ze_strony) {
  //inicjalizacja zmiennych służących do poprawnego odmierzania czasu pozostałego do zresetowania systemu
  unsigned long czasResetu = 0;
  unsigned long zapamietanyCzasResetu = 0;
  resecik = true; //ustawienie odpowiednich informacji
  if (!czy_polecenie_ze_strony) { //jeżeli zresetowanie nie było poleceniem z poziomu strony, to wstaw odpowiednie dane do bazy danych (jeśli polecenie jest z poziomu strony, to dane zostaną wstawione z jej
  //poziomu
    wstaw(false);
  }
  lcd.clear(); //wyczyść ekran LCD
  while (czas != 0) { //dopóki odliczany jest czas do zresetowania systemu
    czasResetu = millis(); //zacznij odmierzać ten czas, aby poprawnie zmniejszać pozostały czas o 1s
    if (czasResetu - zapamietanyCzasResetu >= 1000UL) {
      zapamietanyCzasResetu = czasResetu;
      czas--; //zmniejszaj czas o 1, by prawidłowo wyświetlać odmierzanie czasu na LCD (zmniejszanie w tym miejscu pozwala na wyświetlenie "00:00")
      //co 1s wyświetl odpowiednie informacje na LCD
      lcd.setCursor(0, 0);
      lcd.print(F("Resetowanie systemu:"));
      lcd.setCursor(8, 1);
      lcd.print(F("00:0"));
      lcd.print(czas);
      //na zmianę zapalaj i gaś diodę sygnalizującą resetowanie systemu
      if (digitalRead(stan_resetu) == LOW)
      {
        digitalWrite(stan_resetu, HIGH);
      }
      else
      {
        digitalWrite(stan_resetu, LOW);
      }
    }
  }
  
  //zresetuj wszystkie ustawienia czujników - odblokuj odczytywanie danych z tych, które były ustawione jako uszkodzone
  plomien = '0';
  dym = '0';
  ostrzegacz = '0';
  temp = '0';
  
  //wyzeruj wszystkie zmienne niezbędne do pracy systemu
  confirm = false;
  resecik = false;
  poprzednia_temp = 0;
  licznik_temp = 0;
  pozar1 = false;
  pozar2 = false;
  //wyłącz diody sygnalizujące poszczególne stany systemu
  digitalWrite(stan_rop, LOW);
  digitalWrite(stan_temp, LOW);
  digitalWrite(stan_dym, LOW);
  digitalWrite(stan_plomien, LOW);
  digitalWrite(stan_potwierdz, LOW);
  digitalWrite(alarm1, LOW);
  digitalWrite(alarm2, LOW);
  //wyłącz sygnalizator dźwiękowy
  digitalWrite(sygnalizator_akustyczny, LOW);
  lcd.clear();
  lcd.setCursor(1, 0);
  //nadpisanie tych zmiennych jest niezbędne do późniejszego, prawidłowego odmierzania czasu w poszczególnych fragmentach programu
  czasBezAlarmu = millis();
  zapamietanyCzasBezAlarmu = czasBezAlarmu;
  zapamietanyCzasAlarmuBezPotwierdzenia = czasAlarmuBezPotwierdzenia;
  wstaw(false); //wstaw dane do bazy danych - w tym wypadku wstaw nowy rekord z odpowiednio ustawionymi danymi
  lcd.print(F("System zresetowany")); //wyświetl informację o zresetowaniu systemu
  delay(1000); //wstrzymaj pracę całego systemu na 1s - w celu stabilizacji pracy całego systemu
  lcd.clear(); //wyczyść ekran
}

//******************************************************************************** Odczytywanie ustawień systemu ze skryptów PHP ********************************************************************************

String connectAndRead(String nazwa_wywolywanego_skryptu) { //funkcja określająca, z którego pliku PHP będą odczytywane dane

  if (client.connect(server_addr, 80)) { //jeżeli jest nawiązane połączenie z serwerem na porcie nr 80
    is_online = true; //to ustaw tą zmienną na 'true' (oznacza to, że jest połączenie z serwerem)
    client.print(nazwa_wywolywanego_skryptu); //wywołaj skrypt PHP, zgodnie z otrzymaną nazwą
    client.println(" HTTP/1.1");
    client.println("Host: www.serwer2064773.home.pl");
    client.println("Connection: close");
    client.println();
    return odczytPHP(); //zwróć odczytane dane
  }
  else { //jeżeli nie ma połączenia z serwerem, to znaczy, że sterownik działa w trybie OFFLINE
    is_online = false;
    return "Connection failed";
  }
}

String odczytPHP() {
  //odczytaj i zwróć wszystkie dane zawarte między znakami '<' i '>'

  stringPos = 0;
  memset( &inString, 0, 32 ); //wyczyszczenie tablicy przechowującej odebrane znaki, dane

  while (true) {
    if (client.available()) { //jeżeli nawiązanie połączenia z serwerem (skryptem PHP) jest możliwe, to
      char c = client.read(); //odczytaj pierwszy znak
      if (c == '<' ) { //jeżeli pierwszy odczytany znak to '<', to rozpocznij odczytywanie danych, w innym przypadku odczytanie danych nie będzie możliwe
        startRead = true;
      }
      else if (startRead) { //jeżeli pierwszym znakiem było '<', to kontynuuj odczytywanie danych do momentu odczytania znaku '>'
        if (c != '>') {
          inString[stringPos] = c; //zapisywanie kolejnego odczytanego znaku w tablicy
          stringPos ++; //przejście do kolejnego, pustego elementu tablicy, aby w następnej iteracji można było zapisać kolejny, odebrany znak
        }
        else { //jeżeli aktualnie odebrany znak to '>', to odczytane zostały wszystkie dane
          startRead = false; //zakończ odczyt
          client.stop(); //zakończ połączenie z serwerem
          client.flush();
          return inString; //zwróć, przekaż odczytane dane w miejsce wywołania tej funkcji
        }
      }
    }
  }
}

void StringToInt_pobierz_ustawienia_systemu(bool czy_zresetowano_system) { //pobieranie odpowiednich ustawień systemu, zgodnie z momentem wywołania funkcji w programie
  if (digitalRead(alarm1) == HIGH || digitalRead(alarm2) == HIGH || digitalRead(stan_potwierdz) == HIGH) { //jeżeli występuje alarm pierwszego lub drugiego stopnia bądź potwierdzono alarm, to...
    potwierdz_resetuj = connectAndRead("GET /pobierz_potwierdz_resetuj.php?").toInt(); //odczytaj tylko dane o tym, czy potwierdzono alarm lub zresetowano system z poziomu strony
    //odczytane dane są typu 'STRING', więc zostają zamienione na typ 'INT'
    if (potwierdz_resetuj != 0) { //jeżeli odczytane dane różne od 0 (czyli jest połączenie z serwerem), to...
      for (int i = 1; i >= 0; i--) { //rozbij otrzymany ciąg znaków na poszczególne dane, aby łatwiej je później sprawdzać
        potwierdz_lub_resetuj[i] = potwierdz_resetuj % 10;
        potwierdz_resetuj /= 10;
      }
    }
  }
  else { //jeżeli powyższy warunek nie został spełniony, to pobierz resztę danych - czas do alarmu drugiego stopnia, informacje o uszkodzonych czujnikach oraz temperaturę graniczną
    ustawienia_systemu = connectAndRead("GET /pobierz_ustawienia_systemu.php?").toInt();
    temperatura_graniczna = connectAndRead("GET /pobierz_temp_graniczna.php?").toInt();

    if (temperatura_graniczna == 0) { //jeżeli odczytana temperatura ma wartość 0, to znaczy, że wystąpił błąd z połączeniem z serwerem
      temperatura_graniczna = 70; //więc ustaw jej wartość na 70 stopni
    }
    
    if (ustawienia_systemu != 0) { //jeżeli odczytane dane różne od 0 (czyli jest połączenie z serwerem), to...
      for (int i = 3; i >= 0; i--) { //rozbij otrzymany ciąg znaków na poszczególne dane, aby łatwiej je później sprawdzać - w tym momencie są to dane o uszkodzonych czujnikach
        czy_uszkodzone[i] = ustawienia_systemu % 10;
        ustawienia_systemu /= 10;
      }
      czas_do_alarmu2 = ustawienia_systemu; //pozostały ciąg znaków, to ustawiony czas do wystąpienia alarmu drugiego stopnia
      if (digitalRead(alarm1) == LOW) //zachowaj oryginalny czas do wystąpienia alarmu drugiego stopnia, który został ustawiony na portalu
      {
        aktualnie_ustawiony_czas_do_alarmu2 = czas_do_alarmu2;
      }
    }
    else { //jeżeli nie odebrano połączenia z serwerem, to ustaw domyślny czas
      czas_do_alarmu2 = 20;
    }
  }
}

//************************************************************************************ Wstawianie danych do bazy danych *****************************************************************************************
void wstaw(bool alive_or_increasing_temperature)
{
  if (polacz.connect("46.242.241.62", 80) && !polacz.available()) // jeśli połączono z hostingiem
  {
    polacz.print(F("GET /sterownik.php?")); // wywołaj skrypt PHP umieszczony na podanym hostingu w określonym katalogu i wyślij aktualny stan systemu
    polacz.print(F("czujnik_plomienia="));
    polacz.print(plomien);
    polacz.print(F("&&czujnik_dymu="));
    polacz.print(dym);
    polacz.print(F("&&rop="));
    polacz.print(ostrzegacz);
    polacz.print(F("&&czujnik_temperatury="));
    polacz.print(temp);
    polacz.print(F("&&aktualna_temp="));
    polacz.print(aktualna_temp);
    polacz.print(F("&&potwierdz="));
    polacz.print(confirm);
    polacz.print(F("&&reset="));
    polacz.print(resecik);
    polacz.print(F("&&pozar1="));
    polacz.print(pozar1);
    polacz.print(F("&&pozar2="));
    polacz.print(pozar2);
    polacz.print(F("&&czas_do_alarmu2="));
    if (digitalRead(alarm1) == HIGH) { //jeżeli system w trakcie alarmowania pierwszego stopnia, to wstaw pozostały czas do alarmu drugiego stopnia do bazy danych - w celu prawidłowego wyświetlania pozostałego
      //czasu na stronie, gdy kolejna czujka wykrywa alarm
      polacz.print(czas_do_alarmu2);
    }
    else if (digitalRead(alarm1) == LOW) { //jeżeli system nie alarmuje, to wstaw do bazy danych oryginalny czas do alarmu drugiego stopnia - w celu prawidłowego odmierzania czasu, gdy ponownie pojawi się
      //alarm
      polacz.print(aktualnie_ustawiony_czas_do_alarmu2);
    }
    polacz.print(F("&&is60s_or_increasing_temperature=")); //parametr, dzięki któremu określone będzie, czy wstawić do bazy nowy rekord, czy zaktualizować ostatni rekord o czas ostatniego łączenia z serwerem
    //i aktualną temperaturę
    polacz.print(alive_or_increasing_temperature);
    polacz.println(F(" HTTP/1.1"));
    polacz.println(F("Host: www.serwer2064773.home.pl"));
    polacz.println(F("Connection: close"));
    polacz.println();
  }
  //zakończ połączenie
  if (!polacz.connected())
  {
    polacz.stop();
  }
}
