# GoKasir Flutter API Integration Guide & Response Models

Panduan lengkap ini mendokumentasikan integrasi **GoKasir REST API** ke dalam aplikasi Flutter menggunakan `dio` dan `shared_preferences`, dilengkapi dengan **struktur response JSON ril** dari server untuk setiap endpoint.

---

## 1. Setup & Dependencies

Tambahkan dependensi berikut ke dalam file `pubspec.yaml` Anda:

```yaml
dependencies:
    flutter:
        sdk: flutter
    dio: ^5.4.0
    shared_preferences: ^2.2.2
```

---

## 2. Konfigurasi Base Client (`api_client.dart`)

Client ini otomatis menyuntikkan `Bearer Token` ke setiap request jika user telah login, serta menangani error sesi kedaluwarsa (HTTP 401).

```dart
// lib/core/network/api_client.dart
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  static const String baseUrl = 'http://YOUR_SERVER_IP/api';

  static Dio createDio() {
    final dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (DioException e, handler) {
        if (e.response?.statusCode == 401) {
          // Token kedaluwarsa / tidak valid - Bersihkan session dan arahkan ke login
          SharedPreferences.getInstance().then((prefs) => prefs.clear());
        }
        return handler.next(e);
      },
    ));

    return dio;
  }
}
```

---

## 3. Session & Token Manager (`auth_storage.dart`)

```dart
// lib/core/auth_storage.dart
import 'package:shared_preferences/shared_preferences.dart';

class AuthStorage {
  static const _keyToken    = 'auth_token';
  static const _keyRole     = 'user_role';
  static const _keyStoreId  = 'store_id';
  static const _keyUserId   = 'user_id';

  static Future<void> saveSession({
    required String token,
    required String role,
    required int userId,
    int? storeId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyToken,   token);
    await prefs.setString(_keyRole,    role);
    await prefs.setInt(_keyUserId,     userId);
    if (storeId != null) {
      await prefs.setInt(_keyStoreId, storeId);
    } else {
      await prefs.remove(_keyStoreId);
    }
  }

  static Future<String?> getToken()   async => (await SharedPreferences.getInstance()).getString(_keyToken);
  static Future<String?> getRole()    async => (await SharedPreferences.getInstance()).getString(_keyRole);
  static Future<int?>    getStoreId() async => (await SharedPreferences.getInstance()).getInt(_keyStoreId);
  static Future<int?>    getUserId()   async => (await SharedPreferences.getInstance()).getInt(_keyUserId);
  static Future<void>    clear()       async => (await SharedPreferences.getInstance()).clear();
}
```

---

## 4. Base API Response Model

Semua response API GoKasir dibungkus dalam format standar berikut:

```json
{
  "success": true,
  "message": "Pesan informasi dari server",
  "data": { ... } // Atau array [...] atau null
}
```

Model parser generik di Flutter:

```dart
// lib/core/models/api_response.dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromData,
  ) {
    return ApiResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      data: json['data'] != null && fromData != null
          ? fromData(json['data'])
          : null,
    );
  }
}
```

---

## 5. Auth Service & Response JSONs

### A. Model User

```dart
// lib/features/auth/models/user_model.dart
class UserModel {
  final int id;
  final String name;
  final String email;
  final String role;
  final int? storeId;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.storeId,
  });

  factory UserModel.fromJson(Map<String, dynamic> j) => UserModel(
    id:      j['id'],
    name:    j['name'],
    email:   j['email'],
    role:    j['role'],
    storeId: j['store_id'],
  );
}
```

### B. Service Class

```dart
// lib/features/auth/services/auth_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';
import '../../../core/auth_storage.dart';
import '../models/user_model.dart';

class AuthService {
  final Dio _dio = ApiClient.createDio();

  /// POST /api/auth/register
  Future<Map<String, dynamic>> register({
    required String businessName,
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? storeName,
    String? businessType,
  }) async {
    final res = await _dio.post('/auth/register', data: {
      'business_name':          businessName,
      'name':                   name,
      'email':                  email,
      'password':               password,
      'password_confirmation':  passwordConfirmation,
      if (phone != null)        'phone': phone,
      if (storeName != null)    'store_name': storeName,
      if (businessType != null) 'business_type': businessType,
    });
    return res.data;
  }

  /// POST /api/auth/login
  Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await _dio.post('/auth/login', data: {
      'email':    email,
      'password': password,
    });
    return res.data;
  }

  /// POST /api/auth/logout
  Future<void> logout() async {
    await _dio.post('/auth/logout');
    await AuthStorage.clear();
  }

  /// GET /api/auth/me
  Future<Map<String, dynamic>> getProfile() async {
    final res = await _dio.get('/auth/me');
    return res.data;
  }
}
```

### C. Response JSON Examples

#### `POST /api/auth/register` (Register)

```json
{
    "success": true,
    "message": "Registrasi berhasil dan login otomatis.",
    "data": {
        "token": "4|q8yHjD5sA1Pz9xN...",
        "user": {
            "id": 3,
            "name": "Haji Ahmad",
            "email": "ahmad@barokah.com",
            "role": "owner",
            "store_id": null
        },
        "tenant": {
            "id": 2,
            "business_name": "Toko Barokah Jaya",
            "business_type": "FMCG Retail",
            "email": "ahmad@barokah.com",
            "phone": "081299998888",
            "subscription_plan": "free",
            "status": "active",
            "expired_at": "2026-06-19T12:00:00.000000Z"
        },
        "store": {
            "id": 2,
            "tenant_id": 2,
            "name": "Cabang Utama Barokah"
        }
    }
}
```

#### `POST /api/auth/login` (Login)

```json
{
    "success": true,
    "message": "Login berhasil.",
    "data": {
        "token": "5|p7xKjD5sA1Pz9xM...",
        "user": {
            "id": 1,
            "name": "Owner GoKasir",
            "email": "owner@gokasir.net",
            "role": "owner",
            "store_id": null
        }
    }
}
```

#### `GET /api/auth/me` (Profile detail dengan Store & Tenant)

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "id": 2,
        "tenant_id": 1,
        "store_id": 1,
        "role": "cashier",
        "name": "Kasir GoKasir",
        "email": "cashier@gokasir.net",
        "phone": "08222222222",
        "status": 1,
        "last_login": "2026-05-19 12:00:00",
        "store": {
            "id": 1,
            "name": "Toko GoKasir Utama",
            "city": "Jakarta Selatan"
        },
        "tenant": {
            "id": 1,
            "business_name": "GoKasir Group",
            "subscription_plan": "pro"
        }
    }
}
```

---

## 6. Product Service & Response JSONs

### A. Model Product

```dart
// lib/features/product/models/product_model.dart
class ProductModel {
  final int id;
  final int? categoryId;
  final int? unitId;
  final String name;
  final String? sku;
  final String? barcode;
  final double purchasePrice;
  final double sellingPrice;
  final int minStock;
  final bool isActive;
  final String? categoryName;
  final String? unitCode;

  ProductModel({
    required this.id,
    this.categoryId,
    this.unitId,
    required this.name,
    this.sku,
    this.barcode,
    required this.purchasePrice,
    required this.sellingPrice,
    required this.minStock,
    required this.isActive,
    this.categoryName,
    this.unitCode,
  });

  factory ProductModel.fromJson(Map<String, dynamic> j) => ProductModel(
    id:            j['id'],
    categoryId:    j['category_id'],
    unitId:        j['unit_id'],
    name:          j['name'],
    sku:           j['sku'],
    barcode:       j['barcode'],
    purchasePrice: double.tryParse(j['purchase_price'].toString()) ?? 0,
    sellingPrice:  double.tryParse(j['selling_price'].toString()) ?? 0,
    minStock:      j['min_stock'] ?? 0,
    isActive:      j['is_active'] == true || j['is_active'] == 1,
    categoryName:  j['category']?['name'],
    unitCode:      j['unit']?['code'],
  );
}
```

### B. Service Class

```dart
// lib/features/product/services/product_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class ProductService {
  final Dio _dio = ApiClient.createDio();

  /// GET /api/products
  Future<Map<String, dynamic>> getProducts({
    String? search,
    int? categoryId,
    bool? isActive,
    int page = 1,
  }) async {
    final res = await _dio.get('/products', queryParameters: {
      if (search != null)     'search': search,
      if (categoryId != null) 'category_id': categoryId,
      if (isActive != null)   'is_active': isActive ? 1 : 0,
      'page': page,
    });
    return res.data;
  }

  /// GET /api/products/{id}
  Future<Map<String, dynamic>> getProduct(int id) async {
    final res = await _dio.get('/products/$id');
    return res.data;
  }

  /// GET /api/products/low-stock
  Future<Map<String, dynamic>> getLowStockProducts({int? storeId}) async {
    final res = await _dio.get('/products/low-stock', queryParameters: {
      if (storeId != null) 'store_id': storeId,
    });
    return res.data;
  }

  /// POST /api/products
  Future<Map<String, dynamic>> createProduct(Map<String, dynamic> data) async {
    final res = await _dio.post('/products', data: data);
    return res.data;
  }

  /// PUT /api/products/{id}
  Future<Map<String, dynamic>> updateProduct(int id, Map<String, dynamic> data) async {
    final res = await _dio.put('/products/$id', data: data);
    return res.data;
  }

  /// DELETE /api/products/{id}
  Future<void> deleteProduct(int id) async {
    await _dio.delete('/products/$id');
  }
}
```

### C. Response JSON Examples

#### `GET /api/products` (Product List dengan Pagination)

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "tenant_id": 1,
                "category_id": 2,
                "unit_id": 1,
                "sku": "AQUA-600ML",
                "barcode": "8886008101053",
                "name": "Aqua Botol 600ml",
                "purchase_price": "2500.00",
                "selling_price": "3500.00",
                "min_stock": 10,
                "is_active": 1,
                "category": {
                    "id": 2,
                    "name": "Minuman"
                },
                "unit": {
                    "id": 1,
                    "name": "Pcs",
                    "code": "PCS"
                }
            }
        ],
        "first_page_url": "http://localhost/api/products?page=1",
        "last_page": 1,
        "total": 3
    }
}
```

#### `GET /api/products/{id}` (Detail Single Product dengan Multi-Store Stock)

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "id": 1,
        "tenant_id": 1,
        "category_id": 2,
        "unit_id": 1,
        "sku": "AQUA-600ML",
        "name": "Aqua Botol 600ml",
        "purchase_price": "2500.00",
        "selling_price": "3500.00",
        "min_stock": 10,
        "is_active": 1,
        "category": {
            "id": 2,
            "name": "Minuman"
        },
        "unit": {
            "id": 1,
            "name": "Pcs",
            "code": "PCS"
        },
        "stocks": [
            {
                "id": 1,
                "store_id": 1,
                "product_id": 1,
                "qty": "150.00",
                "store": {
                    "id": 1,
                    "name": "Toko GoKasir Utama"
                }
            }
        ]
    }
}
```

#### `GET /api/products/low-stock?store_id=1` (Peringatan Stok Menipis)

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "id": 3,
            "category_id": 3,
            "unit_id": 1,
            "sku": "CHITATO-BEEF",
            "name": "Chitato Sapi Panggang 68g",
            "min_stock": 5,
            "current_stock": "3.00",
            "category": {
                "id": 3,
                "name": "Snack"
            },
            "unit": {
                "id": 1,
                "code": "PCS"
            }
        }
    ]
}
```

---

## 7. Sale (Checkout & POS) Service & Response JSONs

### A. Model & Request Item

```dart
// lib/features/sale/models/sale_item_request.dart
class SaleItemRequest {
  final int productId;
  final double qty;
  final double price;
  final double discount;

  SaleItemRequest({
    required this.productId,
    required this.qty,
    required this.price,
    this.discount = 0,
  });

  Map<String, dynamic> toJson() => {
    'product_id': productId,
    'qty':        qty,
    'price':      price,
    'discount':   discount,
  };
}
```

### B. Service Class

```dart
// lib/features/sale/services/sale_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';
import '../models/sale_item_request.dart';

class SaleService {
  final Dio _dio = ApiClient.createDio();

  /// POST /api/sales (Proses Checkout POS)
  Future<Map<String, dynamic>> checkout({
    required int storeId,
    required List<SaleItemRequest> items,
    required String paymentMethod, // 'cash'|'qris'|'transfer'|'debit'|'credit'|'tempo'
    required double paidAmount,
    int? customerId,
    double discountAmount = 0,
    double taxAmount = 0,
    String? notes,
    String? dueDate, // Diisi jika paymentMethod == 'tempo' (Format: YYYY-MM-DD)
  }) async {
    final res = await _dio.post('/sales', data: {
      'store_id':        storeId,
      'payment_method':  paymentMethod,
      'paid_amount':     paidAmount,
      'discount_amount': discountAmount,
      'tax_amount':      taxAmount,
      'items':           items.map((e) => e.toJson()).toList(),
      if (customerId != null) 'customer_id': customerId,
      if (notes != null)      'notes': notes,
      if (dueDate != null)    'due_date': dueDate,
    });
    return res.data;
  }

  /// GET /api/sales/today-overview?store_id=
  Future<Map<String, dynamic>> getTodayOverview({int? storeId}) async {
    final res = await _dio.get('/sales/today-overview', queryParameters: {
      if (storeId != null) 'store_id': storeId,
    });
    return res.data;
  }

  /// GET /api/sales
  Future<Map<String, dynamic>> getSales({
    int? storeId,
    String? paymentStatus, // 'paid'|'partial'|'unpaid'
    String? from,
    String? to,
    int page = 1,
  }) async {
    final res = await _dio.get('/sales', queryParameters: {
      if (storeId != null)       'store_id': storeId,
      if (paymentStatus != null) 'payment_status': paymentStatus,
      if (from != null)          'from': from,
      if (to != null)            'to': to,
      'page': page,
    });
    return res.data;
  }

  /// GET /api/sales/{id}
  Future<Map<String, dynamic>> getSaleDetail(int id) async {
    final res = await _dio.get('/sales/$id');
    return res.data;
  }
}
```

### C. Response JSON Examples

#### `GET /api/sales/today-overview` (Ringkasan Penjualan Hari Ini)

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "total_revenue": 1000.0,
        "total_revenue_formatted": "Rp1.000",
        "total_products_sold": 48,
        "total_transactions": 12
    }
}
```

#### `POST /api/sales` (Response Sukses Checkout Cash - Kembalian)

```json
{
    "success": true,
    "message": "Transaksi berhasil.",
    "data": {
        "id": 1,
        "store_id": 1,
        "invoice_number": "INV-20260519-ABC123XYZ",
        "customer_id": null,
        "cashier_id": 2,
        "subtotal": "7000.00",
        "discount_amount": "0.00",
        "tax_amount": "0.00",
        "grand_total": "7000.00",
        "paid_amount": "10000.00",
        "change_amount": "3000.00",
        "payment_method": "cash",
        "payment_status": "paid",
        "transaction_date": "2026-05-19 12:10:00",
        "items": [
            {
                "id": 1,
                "sale_id": 1,
                "product_id": 1,
                "qty": "2.00",
                "price": "3500.00",
                "discount": "0.00",
                "subtotal": "7000.00",
                "product": {
                    "id": 1,
                    "name": "Aqua Botol 600ml"
                }
            }
        ],
        "cashier": {
            "id": 2,
            "name": "Kasir GoKasir"
        }
    }
}
```

#### `POST /api/sales` (Response Checkout Tempo - Otomatis Membuat Data Piutang)

```json
{
    "success": true,
    "message": "Transaksi berhasil.",
    "data": {
        "id": 2,
        "store_id": 1,
        "invoice_number": "INV-20260519-TEMPO999",
        "customer_id": 1,
        "cashier_id": 2,
        "subtotal": "10500.00",
        "grand_total": "10500.00",
        "paid_amount": "0.00",
        "change_amount": "0.00",
        "payment_method": "tempo",
        "payment_status": "unpaid",
        "transaction_date": "2026-05-19 12:15:00",
        "customer": {
            "id": 1,
            "name": "Pelanggan Setia A"
        }
    }
}
```

---

## 8. Stock & Movement Service & Response JSONs

### A. Service Class

```dart
// lib/features/stock/services/stock_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class StockService {
  final Dio _dio = ApiClient.createDio();

  /// GET /api/stocks (Daftar Saldo Stok Aktual per Toko)
  Future<Map<String, dynamic>> getStocks({int? storeId, int page = 1}) async {
    final res = await _dio.get('/stocks', queryParameters: {
      if (storeId != null) 'store_id': storeId,
      'page': page,
    });
    return res.data;
  }

  /// GET /api/stock-movements (Log Mutasi Stok Komprehensif)
  Future<Map<String, dynamic>> getMovements({
    int? storeId,
    int? productId,
    String? type, // 'in'|'out'|'adjustment'
    String? from,
    String? to,
    int page = 1,
  }) async {
    final res = await _dio.get('/stock-movements', queryParameters: {
      if (storeId != null)   'store_id': storeId,
      if (productId != null) 'product_id': productId,
      if (type != null)      'type': type,
      if (from != null)      'from': from,
      if (to != null)        'to': to,
      'page': page,
    });
    return res.data;
  }

  /// POST /api/stock-movements (Mutasi Manual Stok: Masuk / Keluar / Penyesuaian)
  Future<Map<String, dynamic>> mutateStock({
    required int storeId,
    required int productId,
    required String type, // 'in'|'out'|'adjustment'
    required double qty,
    String? notes,
  }) async {
    final res = await _dio.post('/stock-movements', data: {
      'store_id':   storeId,
      'product_id': productId,
      'type':       type,
      'qty':        qty,
      if (notes != null) 'notes': notes,
    });
    return res.data;
  }
}
```

### B. Response JSON Examples

#### `GET /api/stocks?store_id=1`

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "store_id": 1,
                "product_id": 1,
                "qty": "148.00",
                "product": {
                    "id": 1,
                    "name": "Aqua Botol 600ml",
                    "sku": "AQUA-600ML"
                }
            }
        ],
        "total": 3
    }
}
```

#### `POST /api/stock-movements` (Mutasi manual penyesuaian stok opname)

```json
{
    "success": true,
    "message": "Mutasi stok berhasil.",
    "data": {
        "id": 1,
        "store_id": 1,
        "product_id": 1,
        "qty": "160.00" // Jumlah stok terbaru aktual setelah adjustment
    }
}
```

---

## 9. Expense (Biaya Pengeluaran) Service & Response JSONs

### A. Service Class

```dart
// lib/features/expense/services/expense_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class ExpenseService {
  final Dio _dio = ApiClient.createDio();

  /// GET /api/expense-categories
  Future<Map<String, dynamic>> getCategories() async {
    final res = await _dio.get('/expense-categories');
    return res.data;
  }

  /// POST /api/expense-categories
  Future<Map<String, dynamic>> createCategory(String name) async {
    final res = await _dio.post('/expense-categories', data: {'name': name});
    return res.data;
  }

  /// GET /api/expenses
  Future<Map<String, dynamic>> getExpenses({
    int? storeId,
    int? categoryId,
    String? from,
    String? to,
    int page = 1,
  }) async {
    final res = await _dio.get('/expenses', queryParameters: {
      if (storeId != null)    'store_id': storeId,
      if (categoryId != null) 'category_id': categoryId,
      if (from != null)       'from': from,
      if (to != null)         'to': to,
      'page': page,
    });
    return res.data;
  }

  /// POST /api/expenses (Mencatat Biaya Pengeluaran Cabang)
  Future<Map<String, dynamic>> createExpense({
    required int storeId,
    required double amount,
    int? categoryId,
    String? description,
    String? expenseDate,
    String? receiptImage, // Base64 atau URL string file jika diunggah
  }) async {
    final res = await _dio.post('/expenses', data: {
      'store_id':    storeId,
      'amount':      amount,
      if (categoryId != null)   'category_id': categoryId,
      if (description != null)  'description': description,
      if (expenseDate != null)  'expense_date': expenseDate,
      if (receiptImage != null) 'receipt_image': receiptImage,
    });
    return res.data;
  }
}
```

### B. Response JSON Examples

#### `GET /api/expense-categories`

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "id": 1,
            "tenant_id": 1,
            "name": "Listrik & Air"
        },
        {
            "id": 2,
            "tenant_id": 1,
            "name": "Gaji Karyawan"
        }
    ]
}
```

#### `POST /api/expenses` (Berhasil catat pengeluaran)

```json
{
    "success": true,
    "message": "Pengeluaran dicatat.",
    "data": {
        "id": 1,
        "tenant_id": 1,
        "store_id": 1,
        "category_id": 1,
        "amount": "350000.00",
        "expense_date": "2026-05-19",
        "description": "Pembayaran listrik bulan Mei",
        "receipt_image": null,
        "created_by": 2,
        "category": {
            "id": 1,
            "name": "Listrik & Air"
        }
    }
}
```

---

## 10. Customer & Receivable (Piutang & Pelanggan) & JSONs

### A. Service Class

```dart
// lib/features/customer/services/customer_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class CustomerService {
  final Dio _dio = ApiClient.createDio();

  /// GET /api/customers
  Future<Map<String, dynamic>> getCustomers({String? search, int page = 1}) async {
    final res = await _dio.get('/customers', queryParameters: {
      if (search != null) 'search': search,
      'page': page,
    });
    return res.data;
  }

  /// POST /api/customers
  Future<Map<String, dynamic>> createCustomer({
    required String name,
    String? phone,
    String? address,
    double? creditLimit,
  }) async {
    final res = await _dio.post('/customers', data: {
      'name':                    name,
      if (phone != null)        'phone': phone,
      if (address != null)      'address': address,
      if (creditLimit != null)  'credit_limit': creditLimit,
    });
    return res.data;
  }

  /// GET /api/receivables (Daftar Invoice Piutang Kredit Toko)
  Future<Map<String, dynamic>> getReceivables({int? customerId, String? status}) async {
    final res = await _dio.get('/receivables', queryParameters: {
      if (customerId != null) 'customer_id': customerId,
      if (status != null)     'status': status,
    });
    return res.data;
  }

  /// POST /api/receivables/{id}/pay (Bayar Cicilan / Pelunasan Piutang)
  Future<Map<String, dynamic>> payReceivable(int receivableId, {
    required double amount,
    required String paymentMethod,
    String? notes,
  }) async {
    final res = await _dio.post('/receivables/$receivableId/pay', data: {
      'amount':         amount,
      'payment_method': paymentMethod,
      if (notes != null) 'notes': notes,
    });
    return res.data;
  }
}
```

### B. Response JSON Examples

#### `GET /api/customers` (Daftar Pelanggan & Jumlah Utang Berjalan)

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "tenant_id": 1,
                "name": "Pelanggan Setia A",
                "phone": "089988887777",
                "address": "Jl. Mawar Indah No. 10",
                "credit_limit": "2000000.00",
                "current_debt": "10500.00" // Sisa utang berjalan yang belum lunas
            }
        ],
        "total": 1
    }
}
```

#### `POST /api/receivables/{id}/pay` (Response Bayar Cicilan Piutang)

```json
{
    "success": true,
    "message": "Pembayaran piutang dicatat.",
    "data": {
        "id": 1,
        "tenant_id": 1,
        "customer_id": 1,
        "sale_id": 2,
        "total_amount": "10500.00",
        "paid_amount": "5000.00", // Total cicilan terkumpul
        "remaining_amount": "5500.00", // Sisa utang baru
        "status": "partial", // Status terupdate ('partial' / 'paid')
        "due_date": "2026-06-19"
    }
}
```

---

## 11. Report Service & Response JSONs (Owner Only)

### A. Service Class

```dart
// lib/features/report/services/report_service.dart
import 'package:dio/dio.dart';
import '../../../core/network/api_client.dart';

class ReportService {
  final Dio _dio = ApiClient.createDio();

  /// GET /api/reports/summary (Omset, Laba Bersih, Pengeluaran & Sisa Piutang)
  Future<Map<String, dynamic>> getSummary({
    String? from, // YYYY-MM-DD
    String? to,   // YYYY-MM-DD
    int? storeId,
  }) async {
    final res = await _dio.get('/reports/summary', queryParameters: {
      if (from != null)    'from': from,
      if (to != null)      'to': to,
      if (storeId != null) 'store_id': storeId,
    });
    return res.data;
  }

  /// GET /api/reports/sales-by-day (Data Grafis Fluktuasi Penjualan Harian)
  Future<Map<String, dynamic>> getSalesByDay({String? from, String? to, int? storeId}) async {
    final res = await _dio.get('/reports/sales-by-day', queryParameters: {
      if (from != null)    'from': from,
      if (to != null)      'to': to,
      if (storeId != null) 'store_id': storeId,
    });
    return res.data;
  }

  /// GET /api/reports/top-products (10 Terlaris)
  Future<Map<String, dynamic>> getTopProducts({int limit = 10, String? from, String? to}) async {
    final res = await _dio.get('/reports/top-products', queryParameters: {
      'limit':            limit,
      if (from != null)   'from': from,
      if (to != null)     'to': to,
    });
    return res.data;
  }

  /// GET /api/reports/sales-by-payment (Persentase QRIS vs Cash dll)
  Future<Map<String, dynamic>> getSalesByPayment({String? from, String? to}) async {
    final res = await _dio.get('/reports/sales-by-payment', queryParameters: {
      if (from != null) 'from': from,
      if (to != null)   'to': to,
    });
    return res.data;
  }

  /// GET /api/reports/stock-value (Nilai Aset Stok Aktual)
  Future<Map<String, dynamic>> getStockValue({int? storeId}) async {
    final res = await _dio.get('/reports/stock-value', queryParameters: {
      if (storeId != null) 'store_id': storeId,
    });
    return res.data;
  }
}
```

### B. Response JSON Examples

#### `GET /api/reports/summary?from=2026-05-01&to=2026-05-19`

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "period": {
            "from": "2026-05-01",
            "to": "2026-05-19"
        },
        "total_transaction": 120,
        "total_revenue": 4500000.0, // Total omset bruto
        "total_paid": 4000000.0,
        "total_discount": 100000.0,
        "total_tax": 45000.0,
        "total_expense": 1200000.0, // Total biaya pengeluaran operasional
        "net_income": 3300000.0, // Bersih (Omset - Pengeluaran)
        "outstanding_receivable": 500000.0 // Piutang beredar yang belum tertagih
    }
}
```

#### `GET /api/reports/sales-by-day`

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "date": "2026-05-18",
            "total": "1250000.00",
            "count": 15
        },
        {
            "date": "2026-05-19",
            "total": "1800000.00",
            "count": 22
        }
    ]
}
```

#### `GET /api/reports/top-products`

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "id": 2,
            "name": "Indomie Goreng Spesial",
            "total_qty": "85.00",
            "total_revenue": "297500.00"
        },
        {
            "id": 1,
            "name": "Aqua Botol 600ml",
            "total_qty": "70.00",
            "total_revenue": "245000.00"
        }
    ]
}
```

#### `GET /api/reports/sales-by-payment`

```json
{
    "success": true,
    "message": "OK",
    "data": [
        {
            "payment_method": "cash",
            "total": "3200000.00",
            "count": 88
        },
        {
            "payment_method": "qris",
            "total": "1300000.00",
            "count": 32
        }
    ]
}
```

#### `GET /api/reports/stock-value?store_id=1`

```json
{
    "success": true,
    "message": "OK",
    "data": {
        "total_purchase_value": 1450000.0, // Total modal / aset mati
        "total_selling_value": 1980000.0, // Ekspektasi omset jika terjual semua
        "items": [
            {
                "id": 1,
                "name": "Aqua Botol 600ml",
                "purchase_price": "2500.00",
                "selling_price": "3500.00",
                "qty": "148.00",
                "purchase_value": 370000.0,
                "selling_value": 518000.0
            }
        ]
    }
}
```

---

## 12. Penanganan Error Global (`api_error_handler.dart`)

Gunakan handler ini untuk menyaring pesan validasi form (HTTP 422) atau kegagalan koneksi di Flutter:

```dart
// lib/core/network/api_error_handler.dart
import 'package:dio/dio.dart';

class ApiErrorHandler {
  static String getMessage(dynamic error) {
    if (error is DioException) {
      final response = error.response;
      if (response != null) {
        // Cek jika server mengembalikan pesan custom
        final msg = response.data?['message'];
        if (msg != null) return msg.toString();

        switch (response.statusCode) {
          case 401: return 'Sesi login kedaluwarsa. Silakan login kembali.';
          case 403: return 'Akses dibatasi. Anda tidak memiliki izin.';
          case 404: return 'Data tidak ditemukan di server.';
          case 422: return _parseValidationErrors(response.data);
          case 500: return 'Server pusat sedang mengalami masalah internal.';
        }
      }
      if (error.type == DioExceptionType.connectionTimeout) {
        return 'Koneksi timeout. Silakan cek jaringan internet Anda.';
      }
      if (error.type == DioExceptionType.connectionError) {
        return 'Tidak dapat terhubung ke server GoKasir.';
      }
    }
    return 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
  }

  static String _parseValidationErrors(dynamic data) {
    try {
      final errors = data['errors'] as Map<String, dynamic>;
      // Kembalikan pesan error pertama dari list validasi laravel
      return errors.values.first[0].toString();
    } catch (_) {
      return data?['message'] ?? 'Validasi input gagal.';
    }
  }
}
```
