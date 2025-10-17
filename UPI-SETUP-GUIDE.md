# 📱 UPI ID & QR Code Setup Guide

## 🎯 **How to Add Your UPI ID**

### **Method 1: Through Admin Panel (Easy)**

1. **Run the SQL file first** (if not done already):
   - Go to phpMyAdmin: `http://localhost/phpmyadmin`
   - Import: `database/payment_system_update.sql`

2. **Access Admin Settings**:
   - Go to: `http://localhost/Y%20R%20K%20MAHA%20BAZAAR/admin/`
   - Login as admin
   - Click **"Payments"** → **"Payment Settings"**

3. **Update UPI Settings**:
   - **UPI ID**: Replace `yrkmaha@paytm` with your actual UPI ID
   - **Merchant Name**: Replace with your business name
   - Click **"Save Payment Settings"**

### **Method 2: Direct Database Update**

1. **Edit the SQL file** before running it:
   ```sql
   -- In payment_system_update.sql, change:
   ('upi_id', 'YOUR_ACTUAL_UPI_ID@paytm', 'Primary UPI ID for receiving payments'),
   ('merchant_name', 'YOUR_BUSINESS_NAME', 'Merchant name for payment display'),
   ```

2. **Replace with your details**:
   - `YOUR_ACTUAL_UPI_ID@paytm` → `rajesh@paytm` (your UPI ID)
   - `YOUR_BUSINESS_NAME` → `Rajesh Electronics` (your business name)

---

## 📱 **UPI ID Examples**

### **Common UPI ID Formats**:
- **PhonePe**: `yourname@ybl` or `9876543210@ybl`
- **Google Pay**: `yourname@okaxis` or `9876543210@okaxis`
- **Paytm**: `yourname@paytm` or `9876543210@paytm`
- **BHIM**: `yourname@upi` or `9876543210@upi`
- **Bank UPI**: `yourname@sbi`, `yourname@hdfcbank`, etc.

### **How to Find Your UPI ID**:
1. **Open your UPI app** (PhonePe, Google Pay, Paytm, etc.)
2. **Go to Profile/Settings**
3. **Look for "UPI ID" or "VPA"**
4. **Copy your UPI ID**

---

## 🔄 **QR Code Features**

### **Automatic QR Code Generation**:
- ✅ **Dynamic QR codes** generated for each payment
- ✅ **Includes amount** automatically
- ✅ **Order reference** for tracking
- ✅ **Works with all UPI apps**

### **QR Code Contains**:
- **UPI ID**: Your payment address
- **Merchant Name**: Your business name
- **Amount**: Order total (auto-filled)
- **Description**: Order reference number

### **Customer Experience**:
1. **Scan QR code** with any UPI app
2. **Amount pre-filled** automatically
3. **Enter UPI PIN** and pay
4. **Submit transaction ID** on website

---

## 🛠️ **Setup Steps Summary**

### **Step 1: Get Your UPI ID**
```
Example: rajesh@paytm
```

### **Step 2: Update Database**
Either:
- **Admin Panel**: Payments → Settings → Update UPI ID
- **SQL File**: Edit before running

### **Step 3: Test Payment**
1. **Place a test order**
2. **Select UPI payment**
3. **Check if QR code shows your UPI ID**
4. **Verify payment flow works**

---

## 🎨 **QR Code Customization**

### **Current Features**:
- ✅ **300x300 pixel** QR codes
- ✅ **Rounded corners** and shadows
- ✅ **Responsive design**
- ✅ **Multiple UPI app badges**

### **Advanced Options** (Optional):
- **Custom QR colors**
- **Logo embedding**
- **Different sizes**
- **Offline QR generation**

---

## 🔧 **Testing Your Setup**

### **Test Checklist**:
1. ✅ **UPI ID updated** in admin settings
2. ✅ **QR code displays** on payment page
3. ✅ **QR code contains** correct UPI ID
4. ✅ **Amount pre-fills** when scanned
5. ✅ **Payment verification** works in admin

### **Test Payment Flow**:
1. **Add product to cart**
2. **Go to checkout**
3. **Select UPI payment**
4. **Check QR code** shows your UPI ID
5. **Scan with UPI app** (test with small amount)
6. **Submit transaction ID**
7. **Verify in admin panel**

---

## 📞 **Common UPI IDs by Bank/App**

| App/Bank | UPI ID Format | Example |
|----------|---------------|---------|
| **PhonePe** | `name@ybl` | `rajesh@ybl` |
| **Google Pay** | `name@okaxis` | `rajesh@okaxis` |
| **Paytm** | `name@paytm` | `rajesh@paytm` |
| **BHIM** | `name@upi` | `rajesh@upi` |
| **SBI** | `name@sbi` | `rajesh@sbi` |
| **HDFC** | `name@hdfcbank` | `rajesh@hdfcbank` |
| **ICICI** | `name@icici` | `rajesh@icici` |

---

## 🚀 **You're Ready!**

Once you've updated your UPI ID:
- ✅ **Customers can scan QR codes** to pay instantly
- ✅ **Payments go directly** to your UPI account
- ✅ **Admin verification system** confirms orders
- ✅ **Transaction tracking** for all payments

**Your payment system is now live and ready to accept UPI payments!** 🎉💰
