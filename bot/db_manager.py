import mysql.connector
from mysql.connector import pooling
import logging
import time
import json
import os

logger = logging.getLogger("trading_bot")

class DBManager:
    def __init__(self, pool_size=5, config_path=None):
        """
        MySQL veritabanı bağlantı havuzu yöneticisi
        
        :param pool_size: Havuz boyutu (aynı anda kaç bağlantı tutulacak)
        :param config_path: Veritabanı yapılandırma dosyası yolu (Varsayılan: ../config/bot_config.json)
        """
        self.pool_size = pool_size
        self.connection_pool = None
        self.query_stats = {}
        self.last_connections = 0
        
        # Veritabanı yapılandırma bilgilerini al
        self.db_config = self._get_db_config(config_path)
        
        # Bağlantı havuzunu başlat
        self.initialize_pool()
        
    def _get_db_config(self, config_path=None):
        """
        Veritabanı yapılandırma bilgilerini dosyadan okur
        
        :param config_path: Veritabanı yapılandırma dosyası yolu
        :return: Veritabanı yapılandırma sözlüğü
        """
        default_config = {
            'host': 'localhost',
            'user': 'root',
            'password': 'Efsane44.',
            'database': 'trading_bot_db'
        }
        
        try:
            if config_path is None:
                # Script yolunu bul ve config dosyasının yolunu oluştur
                script_dir = os.path.dirname(os.path.abspath(__file__))
                config_path = os.path.join(os.path.dirname(script_dir), 'config', 'bot_config.json')
            
            # Config dosyası varsa oku
            if os.path.exists(config_path):
                with open(config_path, 'r') as config_file:
                    config_data = json.load(config_file)
                
                # Veritabanı bilgilerini al
                if 'database' in config_data:
                    db_config = {
                        'host': config_data['database'].get('host', default_config['host']),
                        'user': config_data['database'].get('user', default_config['user']),
                        'password': config_data['database'].get('password', default_config['password']),
                        'database': config_data['database'].get('database', default_config['database'])
                    }
                    logger.info(f"Veritabanı yapılandırması {config_path} dosyasından okundu")
                    return db_config
        except Exception as e:
            logger.error(f"Veritabanı yapılandırması okunurken hata: {str(e)}")
        
        # Hata durumunda varsayılan yapılandırmayı kullan
        logger.warning("Varsayılan veritabanı yapılandırması kullanılıyor")
        return default_config
        
    def initialize_pool(self):
        """
        Bağlantı havuzunu başlatır
        """
        try:
            self.connection_pool = mysql.connector.pooling.MySQLConnectionPool(
                pool_name="trading_bot_pool",
                pool_size=self.pool_size,
                **self.db_config
            )
            logger.info(f"Veritabanı bağlantı havuzu başlatıldı. Havuz boyutu: {self.pool_size}")
        except Exception as e:
            logger.error(f"Bağlantı havuzu başlatılırken hata: {str(e)}")
            
    def get_connection(self, max_retries=3):
        """
        Havuzdan bir bağlantı alır
        
        :param max_retries: Bağlantı almak için maksimum deneme sayısı
        :return: Veritabanı bağlantısı
        """
        retries = 0
        while retries < max_retries:
            try:
                if self.connection_pool is None:
                    self.initialize_pool()
                    
                conn = self.connection_pool.get_connection()
                self.last_connections += 1
                return conn
            except Exception as e:
                logger.error(f"Veritabanı bağlantısı alırken hata: {str(e)}")
                retries += 1
                if retries < max_retries:
                    logger.info(f"Yeniden deneniyor ({retries}/{max_retries})...")
                    time.sleep(2)  # 2 saniye bekle ve yeniden dene
                    
                    # Havuzu yeniden başlatmayı dene
                    if "pool exhausted" in str(e).lower():
                        logger.warning("Havuz tükendi, yeniden başlatılıyor...")
                        self.initialize_pool()
        
        # Tüm denemeler başarısız olduysa, doğrudan bir bağlantı oluşturmayı dene
        logger.warning("Havuzdan bağlantı alınamadı, doğrudan bağlantı oluşturuluyor...")
        try:
            return mysql.connector.connect(**self.db_config)
        except Exception as direct_error:
            logger.error(f"Doğrudan bağlantı kurulurken hata: {str(direct_error)}")
            return None
        
    def execute_query(self, query, params=None, fetch=True, commit=True):
        """
        SQL sorgusu çalıştırır
        
        :param query: SQL sorgusu
        :param params: Sorgu parametreleri
        :param fetch: Sonuç döndürülecek mi
        :param commit: İşlem commit edilecek mi
        :return: Sorgu sonucu (fetch=True ise)
        """
        conn = None
        cursor = None
        result = None
        start_time = time.time()
        query_type = self._get_query_type(query)
        
        try:
            conn = self.get_connection()
            if not conn:
                logger.error("Veritabanı bağlantısı kurulamadı!")
                return None
                
            cursor = conn.cursor(dictionary=True)
            if params:
                cursor.execute(query, params)
            else:
                cursor.execute(query)
                
            if fetch:
                result = cursor.fetchall()
                
            if commit:
                conn.commit()
            
            # Sorgu istatistiklerini güncelle
            execution_time = time.time() - start_time
            self._update_query_stats(query_type, execution_time)
                
            return result
            
        except Exception as e:
            logger.error(f"SQL sorgusu çalıştırılırken hata: {str(e)}")
            logger.error(f"Sorgu: {query}")
            if params:
                logger.error(f"Parametreler: {params}")
            return None
            
        finally:
            if cursor:
                cursor.close()
            if conn:
                try:
                    conn.close()
                    self.last_connections -= 1
                except Exception:
                    pass
                    
    def _get_query_type(self, query):
        """
        Sorgu tipini belirler (SELECT, INSERT, UPDATE, DELETE, vb.)
        
        :param query: SQL sorgusu
        :return: Sorgu tipi
        """
        query = query.strip().upper()
        if query.startswith("SELECT"):
            return "SELECT"
        elif query.startswith("INSERT"):
            return "INSERT"
        elif query.startswith("UPDATE"):
            return "UPDATE"
        elif query.startswith("DELETE"):
            return "DELETE"
        elif query.startswith("CREATE"):
            return "CREATE"
        elif query.startswith("ALTER"):
            return "ALTER"
        elif query.startswith("DROP"):
            return "DROP"
        elif query.startswith("SHOW"):
            return "SHOW"
        else:
            return "OTHER"
            
    def _update_query_stats(self, query_type, execution_time):
        """
        Sorgu istatistiklerini günceller
        
        :param query_type: Sorgu tipi
        :param execution_time: Çalıştırma süresi (saniye)
        """
        if query_type not in self.query_stats:
            self.query_stats[query_type] = {
                'count': 0,
                'total_time': 0,
                'avg_time': 0,
                'min_time': execution_time,
                'max_time': execution_time
            }
            
        stats = self.query_stats[query_type]
        stats['count'] += 1
        stats['total_time'] += execution_time
        stats['avg_time'] = stats['total_time'] / stats['count']
        stats['min_time'] = min(stats['min_time'], execution_time)
        stats['max_time'] = max(stats['max_time'], execution_time)
        
        # Yavaş sorguları logla
        if execution_time > 1.0:  # 1 saniyeden uzun süren sorgular
            logger.warning(f"Yavaş sorgu tespit edildi! Tip: {query_type}, Süre: {execution_time:.3f} saniye")
                    
    def execute_many(self, query, params_list, commit=True):
        """
        Çoklu SQL sorgusu çalıştırır (batch insert/update)
        
        :param query: SQL sorgusu
        :param params_list: Parametreler listesi
        :param commit: İşlem commit edilecek mi
        :return: Etkilenen satır sayısı
        """
        conn = None
        cursor = None
        affected_rows = 0
        start_time = time.time()
        query_type = self._get_query_type(query) + "_BATCH"
        
        try:
            conn = self.get_connection()
            if not conn:
                logger.error("Veritabanı bağlantısı kurulamadı!")
                return 0
                
            cursor = conn.cursor()
            cursor.executemany(query, params_list)
            
            if commit:
                conn.commit()
                
            affected_rows = cursor.rowcount
            
            # Sorgu istatistiklerini güncelle
            execution_time = time.time() - start_time
            self._update_query_stats(query_type, execution_time)
            
            return affected_rows
            
        except Exception as e:
            logger.error(f"Çoklu SQL sorgusu çalıştırılırken hata: {str(e)}")
            return 0
            
        finally:
            if cursor:
                cursor.close()
            if conn:
                try:
                    conn.close()
                    self.last_connections -= 1
                except Exception:
                    pass
                    
    def get_setting(self, setting_name, default=None):
        """
        Veritabanından bir ayar değeri çeker
        
        :param setting_name: Ayar adı
        :param default: Bulunamazsa kullanılacak varsayılan değer
        :return: Ayar değeri
        """
        query = "SELECT setting_value FROM bot_settings_individual WHERE setting_name = %s ORDER BY id DESC LIMIT 1"
        result = self.execute_query(query, (setting_name,))
        
        if result and len(result) > 0:
            return result[0]['setting_value']
        else:
            return default
            
    def set_setting(self, setting_name, setting_value):
        """
        Veritabanına bir ayar değeri kaydeder
        
        :param setting_name: Ayar adı
        :param setting_value: Ayar değeri
        :return: Başarı durumu
        """
        # Önce var mı kontrol et
        query = "SELECT id FROM bot_settings_individual WHERE setting_name = %s"
        result = self.execute_query(query, (setting_name,))
        
        if result and len(result) > 0:
            # Güncelle
            update_query = "UPDATE bot_settings_individual SET setting_value = %s, updated_at = NOW() WHERE setting_name = %s"
            self.execute_query(update_query, (setting_value, setting_name))
        else:
            # Ekle
            insert_query = "INSERT INTO bot_settings_individual (setting_name, setting_value, created_at) VALUES (%s, %s, NOW())"
            self.execute_query(insert_query, (setting_name, setting_value))
            
        return True
        
    def create_table_if_not_exists(self, table_name, table_schema):
        """
        Eğer yoksa tablo oluşturur
        
        :param table_name: Tablo adı
        :param table_schema: Tablo şeması (CREATE TABLE sonrası)
        :return: Başarı durumu
        """
        try:
            # Tablo var mı kontrol et
            check_query = f"SHOW TABLES LIKE '{table_name}'"
            result = self.execute_query(check_query)
            
            if not result:
                # Tablo yoksa oluştur
                create_query = f"CREATE TABLE {table_name} {table_schema}"
                self.execute_query(create_query)
                logger.info(f"{table_name} tablosu oluşturuldu")
                return True
            else:
                # Tablo zaten var
                return True
                
        except Exception as e:
            logger.error(f"{table_name} tablosu oluşturulurken hata: {str(e)}")
            return False
            
    def get_stats(self):
        """
        Veritabanı sorgu istatistiklerini döndürür
        
        :return: Sorgu istatistikleri
        """
        return {
            'query_stats': self.query_stats,
            'active_connections': self.last_connections,
            'pool_size': self.pool_size
        }
        
    def optimize_pool_size(self):
        """
        Havuz boyutunu optimize eder
        
        :return: Yeni havuz boyutu
        """
        # Son kullanılan bağlantı sayısına göre havuz boyutunu ayarla
        if self.last_connections > self.pool_size * 0.8:  # %80'den fazla kullanılıyorsa
            new_pool_size = min(self.pool_size + 2, 20)  # Maksimum 20 bağlantı
            if new_pool_size != self.pool_size:
                logger.info(f"Havuz boyutu artırılıyor: {self.pool_size} -> {new_pool_size}")
                self.pool_size = new_pool_size
                # Havuzu yeniden başlat
                self.initialize_pool()
        elif self.last_connections < self.pool_size * 0.3 and self.pool_size > 5:  # %30'dan az kullanılıyorsa
            new_pool_size = max(self.pool_size - 1, 5)  # Minimum 5 bağlantı
            if new_pool_size != self.pool_size:
                logger.info(f"Havuz boyutu azaltılıyor: {self.pool_size} -> {new_pool_size}")
                self.pool_size = new_pool_size
                # Havuzu yeniden başlat
                self.initialize_pool()
                
        return self.pool_size

# Singleton nesnesi
db_manager = DBManager()